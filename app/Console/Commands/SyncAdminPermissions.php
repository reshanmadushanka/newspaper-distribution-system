<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SyncAdminPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync-admin 
                            {--fresh : Remove existing admin permissions from config before syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new permissions from config array and sync them with the admin role';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $permissions = Config::get('permissions-sync:admin_permissions', []);

        if (empty($permissions)) {
            $this->warn('No permissions found in config/permissions-sync.php admin_permissions array.');
            return self::SUCCESS;
        }

        $this->info('Found ' . count($permissions) . ' permissions in config array.');

        // Create or retrieve each permission
        $createdCount = 0;
        $existingCount = 0;
        $permissionIds = [];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );

            $permissionIds[] = $permission->id;

            if ($permission->wasRecentlyCreated) {
                $this->info('Created permission: ' . $permissionName);
                $createdCount++;
            } else {
                $this->comment('Permission already exists: ' . $permissionName);
                $existingCount++;
            }
        }

        $this->info("\nResult: $createdCount created, $existingCount already existed.");

        // Get the admin role
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();

        if (!$adminRole) {
            $this->error('Admin role not found. Please run the seeder first.');
            return self::FAILURE;
        }

        // Sync permissions with admin role
        $currentPermissionIds = $adminRole->permissions()->pluck('id')->toArray();

        if ($this->option('fresh')) {
            // Remove only the config-based permissions from admin role
            $configPermissionIds = Permission::whereIn('name', $permissions)
                ->where('guard_name', 'web')
                ->pluck('id')
                ->toArray();

            $adminRole->revokePermissionTo($configPermissionIds);
            $this->info('Removed existing config-based permissions from admin role.');
            $adminRole->syncPermissionsTo($configPermissionIds);
            $this->info('Synced ' . count($configPermissionIds) . ' config-based permissions to admin role.');
        } else {
            // Add new permissions to admin role without removing existing ones
            $newPermissionIds = array_diff($permissionIds, $currentPermissionIds);

            if (!empty($newPermissionIds)) {
                $adminRole->syncPermissionsWithoutDetaching($newPermissionIds);
                $this->info('Added ' . count($newPermissionIds) . ' new permissions to admin role.');
            } else {
                $this->info('No new permissions to add to admin role.');
            }

            // Also ensure all config permissions are assigned
            $adminRole->syncPermissionsWithoutDetaching($permissionIds);
            $this->info('All config permissions are now synced with admin role.');
        }

        $this->info("\nAdmin role permissions: " . $adminRole->permissions()->pluck('name')->implode(', '));
        $this->info('\nSync complete!');

        return self::SUCCESS;
    }
}
