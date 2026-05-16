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
    protected $description = 'Add new permissions from config array and sync them with the super-admin role ONLY';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $permissions = Config::get('permissions-sync.admin_permissions', []);

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

        // Get the super-admin role
        $superAdminRole = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();

        if (!$superAdminRole) {
            $this->error('Super-admin role not found. Please run the seeder first.');
            return self::FAILURE;
        }

        // Sync permissions with super-admin role
        $currentPermissionIds = $superAdminRole->permissions()->pluck('id')->toArray();

        if ($this->option('fresh')) {
            // Remove only the config-based permissions from super-admin role
            $configPermissionIds = Permission::whereIn('name', $permissions)
                ->where('guard_name', 'web')
                ->pluck('id')
                ->toArray();

            $superAdminRole->revokePermissionTo($configPermissionIds);
            $this->info('Removed existing config-based permissions from super-admin role.');
            $superAdminRole->syncPermissions($configPermissionIds);
            $this->info('Synced ' . count($configPermissionIds) . ' config-based permissions to super-admin role.');
        } else {
            // Add new permissions to super-admin role without removing existing ones
            $newPermissionIds = array_diff($permissionIds, $currentPermissionIds);

            if (!empty($newPermissionIds)) {
                foreach ($newPermissionIds as $id) {
                    $superAdminRole->givePermissionTo($id);
                }
                $this->info('Added ' . count($newPermissionIds) . ' new permissions to super-admin role.');
            } else {
                $this->info('No new permissions to add to super-admin role.');
            }

            // Also ensure all config permissions are assigned
            foreach ($permissionIds as $id) {
                if (!in_array($id, $currentPermissionIds)) {
                    $superAdminRole->givePermissionTo($id);
                }
            }
            $this->info('All config permissions are now synced with super-admin role.');
        }

        $this->info("\nSuper-admin role permissions: " . $superAdminRole->permissions()->pluck('name')->implode(', '));
        $this->info('\nSync complete!');

        return self::SUCCESS;
    }
}
