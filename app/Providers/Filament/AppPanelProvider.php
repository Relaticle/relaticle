<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\App\Pages\ApiTokens;
use App\Filament\App\Pages\Auth\Login;
use App\Filament\App\Pages\CreateTeam;
use App\Filament\App\Pages\EditProfile;
use App\Filament\App\Pages\EditTeam;
use App\Filament\App\Resources\CompanyResource;
use App\Http\Middleware\ApplyTenantScopes;
use App\Listeners\SwitchTeam;
use App\Models\Team;
use Filament\Events\TenantSet;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Fortify\Fortify;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Jetstream;
use Relaticle\CustomFields\CustomFieldsPlugin;

final class AppPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        /**
         * Disable Fortify routes
         */
        Fortify::$registersRoutes = false;

        /**
         * Disable Jetstream routes
         */
        Jetstream::$registersRoutes = false;

        /**
         * Listen and switch team if tenant was changed
         */
        Event::listen(
            TenantSet::class,
            SwitchTeam::class,
        );
    }

    /**
     * Configure the Filament admin panel.
     *
     * @throws \Exception
     */
    #[\Override]
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('app')
            ->path('app')
            ->homeUrl(fn (): string => CompanyResource::getUrl('index'))
            ->brandName('Relaticle')
            ->login(Login::class)
            ->passwordReset()
            ->emailVerification()
            ->databaseNotifications()
            ->brandLogoHeight('2.6rem')
            ->brandLogo(fn () => view('filament.app.logo'))
            ->viteTheme('resources/css/app.css')
            ->colors([
                'primary' => '#5D54E8',
            ])
            ->viteTheme('resources/css/filament/app/theme.css')
            ->font('Satoshi')
            ->userMenuItems([
                MenuItem::make()
                    ->label('Profile')
                    ->icon('heroicon-m-user-circle')
                    ->url(fn () => $this->shouldRegisterMenuItem()
                        ? url(EditProfile::getUrl())
                        : url($panel->getPath())),
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->discoverClusters(in: app_path('Filament/App/Clusters'), for: 'App\\Filament\\App\\Clusters')
            ->readOnlyRelationManagersOnResourceViewPagesByDefault(false)
            ->pages([
                EditProfile::class,
                ApiTokens::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Tasks')
                    ->icon('heroicon-o-shopping-cart'),
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
            ->tenantMiddleware(
                [
                    ApplyTenantScopes::class,
                ],
                isPersistent: true
            )
            ->plugins([
                CustomFieldsPlugin::make(),
            ])
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => Blade::render('@env(\'local\')<x-login-link email="manuk.minasyan1@gmail.com" redirect-url="'.url('app').'" />@endenv'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn () => view('filament.auth.social_login_buttons')
            );

        if (Features::hasApiFeatures()) {
            $panel->userMenuItems([
                MenuItem::make()
                    ->label('API Tokens')
                    ->icon('heroicon-o-key')
                    ->url(fn () => $this->shouldRegisterMenuItem()
                        ? url(ApiTokens::getUrl())
                        : url($panel->getPath())),
            ]);
        }

        if (Features::hasTeamFeatures()) {
            $panel
                ->tenant(Team::class)
                ->tenantRegistration(CreateTeam::class)
                ->tenantProfile(EditTeam::class);
        }

        return $panel;
    }

    public function shouldRegisterMenuItem(): bool
    {
        $hasVerifiedEmail = auth()->user()?->hasVerifiedEmail();

        return Filament::hasTenancy()
            ? $hasVerifiedEmail && Filament::getTenant()
            : $hasVerifiedEmail;
    }
}
