<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Additional Permissions for Admin Role
    |--------------------------------------------------------------------------
    |
    | These permissions will be added to the database and synced with the
    | 'admin' role. This allows you to define new permissions in one place
    | and automatically have them added to the admin role.
    |
    */

    'admin_permissions' => [
        'manage settings',
        'view reports',
        'export data',
        'send sms notifications',
        'manage pricing',
        'manage newspapers',
        'view newspapers',
        'create newspapers',
        'edit newspapers',
        'delete newspapers'
    ],

];