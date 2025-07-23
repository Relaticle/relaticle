<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\App\Pages\ApiTokens;
use App\Filament\App\Pages\Auth\Login;
use App\Filament\App\Pages\Auth\Register;
use App\Filament\App\Pages\CreateTeam;
use App\Filament\App\Pages\EditProfile;
use App\Filament\App\Pages\EditTeam;
use App\Filament\App\Resources\CompanyResource;
use App\Http\Middleware\ApplyTenantScopes;
use App\Listeners\SwitchTeam;
use App\Models\Team;
use Exception;
use Filament\Events\TenantSet;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Components\Section;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Jetstream\Features;
use Relaticle\CustomFields\CustomFieldsPlugin;

final class AppPanelProvider extends PanelProvider
{
    /**
     * Perform post-registration booting of components.
     */
    public function boot(): void
    {
        /**
         * Listen and switch team if tenant was changed
         */
        Event::listen(
            TenantSet::class,
            SwitchTeam::class,
        );

        Section::configureUsing(fn (Section $section): \Filament\Schemas\Components\Section => $section->compact());
        Table::configureUsing(fn (Table $table): \Filament\Tables\Table => $table);
    }

    /**
     * Configure the Filament admin panel.
     *
     * @throws Exception
     */
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('app')
            ->domain('app.'.parse_url((string) config('app.url'))['host'])
            ->homeUrl(fn (): string => CompanyResource::getUrl())
            ->brandName('Relaticle')
            ->login(Login::class)
            ->registration(Register::class)
            ->passwordReset()
            ->emailVerification()
            ->databaseNotifications()
            ->brandLogoHeight('2.6rem')
            ->brandLogo(fn () => view('filament.app.logo'))
            ->viteTheme('resources/css/app.css')
            ->colors([
                'primary' => [
                    50 => 'oklch(0.969 0.016 293.756)',
                    100 => 'oklch(0.943 0.028 294.588)',
                    200 => 'oklch(0.894 0.055 293.283)',
                    300 => 'oklch(0.811 0.101 293.571)',
                    400 => 'oklch(0.709 0.159 293.541)',
                    500 => 'oklch(0.606 0.219 292.717)',
                    600 => 'oklch(0.541 0.247 293.009)',
                    700 => 'oklch(0.491 0.241 292.581)',
                    800 => 'oklch(0.432 0.211 292.759)',
                    900 => 'oklch(0.380 0.178 293.745)',
                    950 => 'oklch(0.283 0.135 291.089)',
                    'DEFAULT' => 'oklch(0.541 0.247 293.009)',
                ],
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
                CustomFieldsPlugin::make()->authorize(fn () => Gate::check('update', Filament::getTenant())),
            ])
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => Blade::render('@env(\'local\')<x-login-link email="manuk.minasyan1@gmail.com" redirect-url="'.url('/').'" />@endenv'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn () => view('filament.auth.social_login_buttons')
            )
            ->renderHook(
                PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE,
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
        $hasVerifiedEmail = Auth::user()?->hasVerifiedEmail();

        return Filament::hasTenancy()
            ? $hasVerifiedEmail && Filament::getTenant()
            : $hasVerifiedEmail;
    }
}
