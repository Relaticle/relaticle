<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | This section controls the features of the Custom Fields package.
    | You can enable or disable features as needed.
    |
    */
    'features' => [
        'conditional_visibility' => [
            'enabled' => true,
        ],
        'encryption' => [
            'enabled' => true,
        ],
        'select_option_colors' => [
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity Resources Customization
    |--------------------------------------------------------------------------
    |
    | This section allows you to customize the behavior of entity resources,
    | such as enabling table column toggling and setting default visibility.
    |
    */
    'resource' => [
        'table' => [
            'columns' => [
                'enabled' => true,
            ],
            'columns_toggleable' => [
                'enabled' => true,
                'user_control' => true,
                'hidden_by_default' => true,
            ],
            'filters' => [
                'enabled' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Types Availability
    |--------------------------------------------------------------------------
    |
    | Configure which field types are available in the application.
    | Use 'enabled' to allow only specific types, or 'disabled' to exclude types.
    | Empty arrays mean no restrictions apply.
    |
    */
    'field_types' => [
        'enabled' => [
            // Empty array = all field types enabled (default)
        ],
        'disabled' => [
            // Specify field type keys to disable:
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Field Types Configuration
    |--------------------------------------------------------------------------
    |
    | This section controls the Custom Field Types.
    | This allows you to customize the behavior of the field types.
    |
    */
    'field_types_configuration' => [
        'date' => [
            'native' => false,
            'format' => 'Y-m-d',
            'display_format' => null,
        ],
        'date_time' => [
            'native' => false,
            'format' => 'Y-m-d H:i:s',
            'display_format' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Fields Resource Configuration
    |--------------------------------------------------------------------------
    |
    | This section controls the Custom Fields resource.
    | This allows you to customize the behavior of the resource.
    |
    */
    'custom_fields_management' => [
        'should_register_navigation' => true,
        'slug' => 'custom-fields',
        'navigation_sort' => -1,
        'navigation_group' => true,
        'cluster' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity Management Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how entities (models that can have custom fields) are
    | discovered, registered, and managed throughout the system.
    |
    */
    'entity_management' => [
        /*
        | Enable automatic discovery of entities from configured paths
        | and Filament Resources. When disabled, only manually registered
        | entities will be available.
        */
        'auto_discover_entities' => env('CUSTOM_FIELDS_AUTO_DISCOVER_ENTITIES', true),

        /*
        | Directories to scan for models implementing HasCustomFields.
        | All models in these directories will be automatically discovered.
        */
        'entity_discovery_paths' => [
            app_path('Models'),
        ],

        /*
        | Namespaces to scan for entity models.
        | Used when discovery paths are not sufficient.
        */
        'entity_discovery_namespaces' => [
            'App\\Models',
        ],

        /*
        | Enable caching of discovered entities for better performance.
        | Disable during development for immediate updates.
        */
        'cache_entities' => env('CUSTOM_FIELDS_CACHE_ENTITIES', true),

        /*
        | Models to exclude from automatic discovery.
        | These models will not be available as entities even if they
        | implement HasCustomFields.
        */
        'excluded_models' => [
            // App\Models\User::class,
        ],

        /*
        | Manually registered entities.
        | Use this to register entities without Resources or to override
        | auto-discovered configuration.
        */
        'entities' => [
            //
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
    | Database Migrations Paths
    |--------------------------------------------------------------------------
    |
    | In these directories custom fields migrations will be stored and ran when migrating. A custom fields
    | migration created via the make:custom-fields-migration command will be stored in the first path or
    | a custom defined path when running the command.
    |
    */
    'migrations_paths' => [
        database_path('custom-fields'),
    ],

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
        'custom_field_sections' => 'custom_field_sections',
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
