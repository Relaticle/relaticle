<?php

declare(strict_types=1);

namespace Relaticle\Admin;

use Awcodes\Overlook\OverlookPlugin;
use Awcodes\Overlook\Widgets\OverlookWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Relaticle\Admin\Filament\Pages\Dashboard;

final class AdminPanelProvider extends PanelProvider
{
    /**
     * @throws \Exception
     */
    public function panel(Panel $panel): Panel
    {
        $appUrl = (string) config('app.url');
        $urlParts = parse_url($appUrl);
        $host = $urlParts['host'] ?? 'localhost';

        return $panel
            ->id('admin')
            ->domain('admin.'.$host)
            ->login()
            ->spa()
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->brandName('Relaticle Admin')
            ->discoverResources(in: base_path('app-modules/Admin/src/Filament/Resources'), for: 'Relaticle\\Admin\\Filament\\Resources')
            ->discoverPages(in: base_path('app-modules/Admin/src/Filament/Pages'), for: 'Relaticle\\Admin\\Filament\\Pages')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('User Management'),
                NavigationGroup::make()
                    ->label('CRM'),
                NavigationGroup::make()
                    ->label('Task Management'),
                NavigationGroup::make()
                    ->label('Content'),
            ])
            ->globalSearch()
            ->darkMode()
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop()
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: base_path('app-modules/Admin/src/Filament/Widgets'), for: 'Relaticle\\Admin\\Filament\\Widgets')
            ->widgets([
                OverlookWidget::class,
            ])
            ->databaseNotifications()
            ->plugins([
                OverlookPlugin::make()
                    ->sort(5)
                    ->columns([
                        'default' => 2,
                        'sm' => 3,
                        'md' => 4,
                        'lg' => 5,
                        'xl' => 6,
                        '2xl' => 6,
                    ]),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css');
    }
}
