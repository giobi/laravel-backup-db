<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the backup management routes are registered.
    |
    */
    'enable_routes' => true,
    'route_prefix' => 'backups',
    'middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | Define the authorization gate that controls access to the backup
    | management interface. Users must pass this gate to access the routes.
    |
    */
    'auth_gate' => 'admin',

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure the log channel used for backup restoration operations.
    | This helps track all restore activities for auditing purposes.
    |
    */
    'log_channel' => 'daily',

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Customize the appearance and behavior of the backup management interface.
    |
    */
    'ui' => [
        'title' => 'Database Backup Manager',
        'theme' => 'dark', // 'dark' or 'light'
        'per_page' => 10,
    ],
];