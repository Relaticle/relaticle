<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Entity Resources Configuration
    |--------------------------------------------------------------------------
    |
    | This section controls which Filament resources are allowed or disallowed
    | to have custom fields. You can specify allowed resources, disallowed
    | resources, or leave them empty to use default behavior.
    |
    */
    'allowed_entity_resources' => [
        // App\Filament\Resources\UserResource::class,
    ],

    'disallowed_entity_resources' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Lookup Resources Configuration
    |--------------------------------------------------------------------------
    |
    | Define which Filament resources can be used as lookups. You can specify
    | allowed resources, disallowed resources, or leave them empty to use
    | default behavior.
    |
    */
    'allowed_lookup_resources' => [
        //
    ],

    'disallowed_lookup_resources' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Table Configuration
    |--------------------------------------------------------------------------
    |
    | This section allows you to customize the behavior of resource tables,
    | such as enabling column toggling and setting default visibility.
    |
    */
    'resource' => [
        'table' => [
            'columns_toggleable' => [
                'enabled' => true,
                'hidden_by_default' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Awareness Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, this feature implements multi-tenancy using the specified
    | tenant foreign key. Enable this before running migrations to automatically
    | register the tenant foreign key.
    |
    */
    'tenant_aware' => true,

    /*
    |--------------------------------------------------------------------------
    | Database Table Names
    |--------------------------------------------------------------------------
    |
    | You can specify custom table names for the package's database tables here.
    | These tables will be used to store custom fields, their values, and options.
    |
    */
    'table_names' => [
        'custom_fields' => 'custom_fields',
        'custom_field_values' => 'custom_field_values',
        'custom_field_options' => 'custom_field_options',
    ],

    /*
    |--------------------------------------------------------------------------
    | Column Names
    |--------------------------------------------------------------------------
    |
    | Here you can customize the names of specific columns used by the package.
    | For example, you can change the name of the tenant foreign key if needed.
    |
    */
    'column_names' => [
        'tenant_foreign_key' => 'tenant_id',
    ],
];
