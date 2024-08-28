<?php

// config for ManukMinasyan/FilamentAttribute
return [
    'entities' => [
        [
            'entity' => \App\Models\User::class,
            'resource' => \App\Filament\Resources\UserResource::class,
        ]
    ],

    'lookup_models' => [
        [
            'model' => \App\Models\User::class,
            'resource' => \App\Filament\Resources\UserResource::class,
        ]
    ]
];
