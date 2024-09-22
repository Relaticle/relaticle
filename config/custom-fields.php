<?php

return [
    'allowed_entity_resources' => [
        //        App\Filament\Resources\UserResource::class
    ],

    'disallowed_entity_resources' => [],

    'allowed_lookup_resources' => [],

    'disallowed_lookup_resources' => [],

    'resource' => [
        'table' => [
            'columns_toggleable' => [
                'enabled' => true,
                'hidden_by_default' => true,
            ],
        ],
    ],

    /**
     * Teams Feature.
     * When set to true the package implements teams using the 'team_foreign_key'.
     * If you want the migrations to register the 'team_foreign_key', you must
     * set this to true before doing the migration.
     * If you already did the migration then you must make a new migration.
     */
    'teams' => true,

    'table_names' => [
        'custom_fields' => 'custom_fields',
        'custom_field_values' => 'custom_field_values',
        'custom_field_options' => 'custom_field_options',
    ],

    'column_names' => [
        /*
         * Change this if you want to use the teams feature and your related model's
         * foreign key is other than `team_id`.
         */
        'team_foreign_key' => 'team_id',
    ],
];
