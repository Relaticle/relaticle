<?php

declare(strict_types=1);

use Relaticle\CustomFields\Entities\Configuration\EntityConfiguration;
use Relaticle\CustomFields\FieldSystem\SystemConfig;

return [
    /*
    |--------------------------------------------------------------------------
    | Entity Configuration
    |--------------------------------------------------------------------------
    |
    | Configure entities (models that can have custom fields) using the
    | clean, type-safe fluent builder interface.
    |
    */
    'entity_configuration' => EntityConfiguration::configure()
        ->discover(app_path('Models'))
        ->include([
            App\Models\People::class,
            App\Models\Company::class,
            App\Models\Opportunity::class,
            App\Models\Task::class,
            App\Models\Note::class,
        ])
        ->cache(),

    /*
    |--------------------------------------------------------------------------
    | Advanced Field Type Configuration
    |--------------------------------------------------------------------------
    |
    | Configure field types using the powerful fluent builder API.
    | This provides advanced control over validation, security, and behavior.
    |
    */
    'field_type_configuration' => SystemConfig::configure()
        // Control which field types are available globally
        ->enabled([]) // Empty = all enabled, or specify: ['text', 'email', 'select']
        ->disabled(['rich-editor']) // Disable specific field types
        ->discover(true)
        ->cache(enabled: true, ttl: 3600),

    /*
    |--------------------------------------------------------------------------
    | Features Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable package features. All features are enabled by default.
    |
    */
    'features' => [
        'conditional_visibility' => true,
        'encryption' => true,
        'select_option_colors' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Configuration
    |--------------------------------------------------------------------------
    |
    | Customize the behavior of entity resources in Filament.
    |
    */
    'resource' => [
        'table' => [
            'columns' => true,
            'columns_toggleable' => true,
            'filters' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Management Interface
    |--------------------------------------------------------------------------
    |
    | Configure the Custom Fields management interface in Filament.
    |
    */
    'management' => [
        'enabled' => true,
        'slug' => 'custom-fields',
        'navigation_sort' => -1,
        'navigation_group' => true,
        'cluster' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | Enable multi-tenancy support with automatic tenant isolation.
    |
    */
    'tenant_aware' => false,

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database table names and migration paths.
    |
    */
    'database' => [
        'migrations_path' => database_path('custom-fields'),
        'table_names' => [
            'custom_field_sections' => 'custom_field_sections',
            'custom_fields' => 'custom_fields',
            'custom_field_values' => 'custom_field_values',
            'custom_field_options' => 'custom_field_options',
        ],
        'column_names' => [
            'tenant_foreign_key' => 'tenant_id',
        ],
    ],
];
