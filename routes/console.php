<?php

use App\Console\Commands\SyncAdminPermissions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('permissions:sync-admin', function () {
    $permissions = Config::get('permissions-sync.admin_permissions', []);

    if (empty($permissions)) {
        $this->warn('No permissions found in config/permissions-sync.php admin_permissions array.');
        return;
    }

    $this->info('Found ' . count($permissions) . ' permissions in config array.');

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

    $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();

    if (!$adminRole) {
        $this->error('Admin role not found. Please run the seeder first.');
        return;
    }

    $currentPermissionIds = $adminRole->permissions()->pluck('id')->toArray();
    $newPermissionIds = array_diff($permissionIds, $currentPermissionIds);

    if (!empty($newPermissionIds)) {
        foreach ($newPermissionIds as $id) {
            $adminRole->givePermissionTo($id);
        }
        $this->info('Added ' . count($newPermissionIds) . ' new permissions to admin role.');
    } else {
        $this->info('No new permissions to add to admin role.');
    }

    $this->info("\nAdmin role permissions: " . $adminRole->permissions()->pluck('name')->implode(', '));
    $this->info('\nSync complete!');
})->purpose('Add new permissions from config array and sync them with the admin role');
