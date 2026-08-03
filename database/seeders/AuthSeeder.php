<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthSeeder extends Seeder
{
    public function run(): void
    {
        // config/permissions-sync.php is the source of truth for what super-admin
        // can do — the same list `permissions:sync-admin` applies. Merging it here
        // (rather than repeating a hardcoded list) stops the seeder drifting behind
        // newly added permissions, which is how a freshly seeded super-admin ended
        // up without 'use ai chat' and lost the AI chat widget.
        $permissions = array_values(array_unique([...[
            'manage users',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage roles',
            'manage permissions',
            'manage shops',
            'view shops',
            'create shops',
            'edit shops',
            'delete shops',
            'manage invoices',
            'view invoices',
            'create invoices',
            'view dashboard',
        ], ...config('permissions-sync.admin_permissions', [])]));

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $superAdmin->syncPermissions($permissions);
        $admin->syncPermissions(['manage users', 'manage roles', 'manage permissions']);

        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ],
        );

        $user->assignRole($superAdmin);
    }
}
