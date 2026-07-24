<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Additional Permissions for Super Admin Role
    |--------------------------------------------------------------------------
    |
    | These permissions will be added to the database and synced with the
    | 'super-admin' role ONLY (not the 'admin' role).
    | Run: php artisan permissions:sync-admin
    |
    */

    'admin_permissions' => [
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
        'manage settings',
        'view reports',
        'export data',
        'send sms notifications',
        'manage pricing',
        'manage newspapers',
        'view newspapers',
        'create newspapers',
        'edit newspapers',
        'delete newspapers',
        'manage invoices',
        'view invoices',
        'create invoices',
        'view dashboard',
        'view daily sales',
        'manage system invoices',
        'view system invoices',
        'create system invoices',
        'view daily sales',
        'auto generate invoice',
        'use ai chat',
    ],

];
