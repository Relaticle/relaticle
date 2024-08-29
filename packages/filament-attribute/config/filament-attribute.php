<?php

// config for ManukMinasyan/FilamentAttribute
return [
    'allowed_resources' => [
        \App\Filament\Resources\UserResource::class,
    ],

    'disallowed_resources' => [
        \App\Filament\Resources\CompanyResource::class,
    ],

    'database' => [
        'attributes_table' => 'attributes',
        'attribute_values_table' => 'attribute_values',
        'attribute_options_table' => 'attribute_options',
    ],
];
