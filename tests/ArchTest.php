<?php

declare(strict_types=1);

arch()->preset()->php();

// arch()->preset()->strict();

arch()->preset()->security()->ignoring('assert');

arch()->preset()
    ->laravel()
    ->ignoring([
        'App\Providers\AppServiceProvider',
        'App\Providers\Filament\AppPanelProvider',
        'Relaticle\Admin\AdminPanelProvider',
        'App\Enums\EnumValues',
        'App\Enums\CustomFields\CustomFieldTrait',
    ]);

arch('strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('avoid open for extension')
    ->expect('App')
    ->classes()
    ->toBeFinal()
    ->ignoring([
        //        App\Services\Autocomplete\Types\Type::class,
    ]);

arch('ensure no extends')
    ->expect('App')
    ->classes()
    ->not
    ->toBeAbstract();

arch('avoid mutation')
    ->expect('App')
    ->classes()
    ->toBeReadonly()
    ->ignoring([
        'App\Console\Commands',
        'App\Exceptions',
        'App\Filament',
        'App\Http\Requests',
        'App\Jobs',
        'App\Livewire',
        'App\Mail',
        'App\Models',
        'App\Notifications',
        'App\Providers',
        'App\View',
        'App\Providers\Filament',
    ]);

arch('avoid inheritance')
    ->expect('App')
    ->classes()
    ->toExtendNothing()
    ->ignoring([
        'App\Console\Commands',
        'App\Exceptions',
        'App\Filament',
        'App\Http\Requests',
        'App\Jobs',
        'App\Livewire',
        'App\Mail',
        'App\Models',
        'App\Notifications',
        'App\Providers',
        'App\View',
    ]);

// arch('annotations')
//    ->expect('App')
//    ->toHavePropertiesDocumented()
//    ->toHaveMethodsDocumented();
