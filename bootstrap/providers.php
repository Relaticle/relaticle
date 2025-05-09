<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    Relaticle\Admin\AdminPanelProvider::class,
    App\Providers\Filament\AppPanelProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\JetstreamServiceProvider::class,
    App\Providers\MacroServiceProvider::class,
    Relaticle\Documentation\DocumentationServiceProvider::class,
];
