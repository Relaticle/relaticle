<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Models\Company;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\CustomFieldSection;
use App\Models\CustomFieldValue;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\GitHubService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;
use Relaticle\CustomFields\CustomFields;
use Relaticle\ImportWizard\Models\Export;
use Relaticle\ImportWizard\Models\Import;
use Relaticle\SystemAdmin\Models\SystemAdministrator;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\Filament\Auth\Http\Responses\Contracts\LoginResponse::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePolicies();
        $this->configureModels();
        $this->configureFilament();
        $this->configureGitHubStars();
        $this->configureLivewire();
    }

    private function configurePolicies(): void
    {
        Gate::guessPolicyNamesUsing(function (string $modelClass): ?string {
            try {
                $currentPanelId = Filament::getCurrentPanel()?->getId();

                if ($currentPanelId === 'sysadmin') {
                    $modelName = class_basename($modelClass);
                    $systemAdminPolicy = "Relaticle\\SystemAdmin\\Policies\\{$modelName}Policy";

                    // Return SystemAdmin policy if it exists
                    if (class_exists($systemAdminPolicy)) {
                        return $systemAdminPolicy;
                    }
                }
            } catch (\Exception) {
                // Fallback for non-Filament contexts
            }

            // Use Laravel's default policy discovery logic
            return $this->getDefaultLaravelPolicyName($modelClass);
        });
    }

    private function getDefaultLaravelPolicyName(string $modelClass): ?string
    {
        // Replicate Laravel's default policy discovery logic from Gate.php:723-736
        $classDirname = str_replace('/', '\\', dirname(str_replace('\\', '/', $modelClass)));
        $classDirnameSegments = explode('\\', $classDirname);

        $candidates = collect();
        // Generate all possible policy paths
        $counter = count($classDirnameSegments);

        // Generate all possible policy paths
        for ($index = 0; $index < $counter; $index++) {
            $classDirname = implode('\\', array_slice($classDirnameSegments, 0, $index));
            $candidates->push($classDirname.'\\Policies\\'.class_basename($modelClass).'Policy');
        }

        // Add Models-specific paths if the model is in a Models directory
        if (str_contains($classDirname, '\\Models\\')) {
            $candidates = $candidates
                ->concat([str_replace('\\Models\\', '\\Policies\\', $classDirname).'\\'.class_basename($modelClass).'Policy'])
                ->concat([str_replace('\\Models\\', '\\Models\\Policies\\', $classDirname).'\\'.class_basename($modelClass).'Policy']);
        }

        // Return the first existing class, or fallback
        $existingPolicy = $candidates->reverse()->first(fn (string $class): bool => class_exists($class));

        return $existingPolicy ?: $classDirname.'\\Policies\\'.class_basename($modelClass).'Policy';
    }

    /**
     * Configure custom Livewire components.
     */
    private function configureLivewire(): void
    {
        // Custom Livewire components can be registered here
    }

    /**
     * Configure the models for the application.
     */
    private function configureModels(): void
    {
        Model::unguard();
        //        Model::shouldBeStrict(! $this->app->isProduction()); // TODO: Uncomment this line to enable strict mode in local env

        Relation::enforceMorphMap([
            'team' => Team::class,
            'user' => User::class,
            'people' => People::class,
            'company' => Company::class,
            'opportunity' => Opportunity::class,
            'task' => Task::class,
            'note' => Note::class,
            'system_administrator' => SystemAdministrator::class,
            'import' => Import::class,
            'export' => Export::class,
        ]);

        // Bind our custom Import and Export models to the Filament models
        $this->app->bind(\Filament\Actions\Imports\Models\Import::class, Import::class);
        $this->app->bind(\Filament\Actions\Exports\Models\Export::class, Export::class);

        // Use custom models for custom-fields package
        CustomFields::useCustomFieldModel(CustomField::class);
        CustomFields::useSectionModel(CustomFieldSection::class);
        CustomFields::useOptionModel(CustomFieldOption::class);
        CustomFields::useValueModel(CustomFieldValue::class);
    }

    /**
     * Configure Filament.
     */
    private function configureFilament(): void
    {
        $this->registerPhosphorIcons();

        $slideOverActions = ['create', 'edit', 'view'];

        Action::configureUsing(function (Action $action) use ($slideOverActions): Action {
            if (in_array($action->getName(), $slideOverActions)) {
                return $action->slideOver();
            }

            return $action;
        });
    }

    /**
     * Register Phosphor icons as replacements for Heroicons in Filament.
     */
    private function registerPhosphorIcons(): void
    {
        FilamentIcon::register([
            // Panels - Sidebar & Navigation
            'panels::global-search.field' => 'phosphor-o-magnifying-glass',
            'panels::sidebar.collapse-button' => 'phosphor-o-caret-left',
            'panels::sidebar.collapse-button.rtl' => 'phosphor-o-caret-right',
            'panels::sidebar.expand-button' => 'phosphor-o-caret-right',
            'panels::sidebar.expand-button.rtl' => 'phosphor-o-caret-left',
            'panels::sidebar.group.collapse-button' => 'phosphor-o-caret-down',
            'panels::topbar.open-sidebar-button' => 'phosphor-o-list',
            'panels::topbar.close-sidebar-button' => 'phosphor-o-x',
            'panels::topbar.group.toggle-button' => 'phosphor-o-caret-down',

            // Panels - Theme Switcher
            'panels::theme-switcher.light-button' => 'phosphor-o-sun',
            'panels::theme-switcher.dark-button' => 'phosphor-o-moon',
            'panels::theme-switcher.system-button' => 'phosphor-o-desktop',

            // Panels - User Menu
            'panels::user-menu.profile-item' => 'phosphor-o-user-circle',
            'panels::user-menu.logout-button' => 'phosphor-o-sign-out',
            'panels::user-menu.toggle-button' => 'phosphor-o-caret-down',

            // Panels - Tenant Menu
            'panels::tenant-menu.toggle-button' => 'phosphor-o-caret-down',
            'panels::tenant-menu.billing-button' => 'phosphor-o-currency-dollar',
            'panels::tenant-menu.profile-button' => 'phosphor-o-user-circle',

            // Panels - Notifications
            'panels::topbar.open-database-notifications-button' => 'phosphor-o-bell',
            'panels::sidebar.open-database-notifications-button' => 'phosphor-o-bell',

            // Panels - Pages
            'panels::pages.dashboard.navigation-item' => 'phosphor-d-house',
            'panels::pages.dashboard.actions.filter' => 'phosphor-o-funnel',

            // Actions
            'actions::action-group' => 'phosphor-o-dots-three',
            'actions::create-action.grouped' => 'phosphor-o-plus',
            'actions::delete-action' => 'phosphor-o-trash',
            'actions::delete-action.grouped' => 'phosphor-o-trash',
            'actions::delete-action.modal' => 'phosphor-o-trash',
            'actions::edit-action' => 'phosphor-o-pencil-simple',
            'actions::edit-action.grouped' => 'phosphor-o-pencil-simple',
            'actions::view-action' => 'phosphor-o-eye',
            'actions::view-action.grouped' => 'phosphor-o-eye',
            'actions::replicate-action' => 'phosphor-o-files',
            'actions::replicate-action.grouped' => 'phosphor-o-files',
            'actions::restore-action' => 'phosphor-o-arrows-clockwise',
            'actions::restore-action.grouped' => 'phosphor-o-arrows-clockwise',
            'actions::restore-action.modal' => 'phosphor-o-arrows-clockwise',
            'actions::force-delete-action' => 'phosphor-o-trash',
            'actions::force-delete-action.grouped' => 'phosphor-o-trash',
            'actions::force-delete-action.modal' => 'phosphor-o-trash',
            'actions::import-action.grouped' => 'phosphor-o-upload',
            'actions::export-action.grouped' => 'phosphor-o-arrow-down',
            'actions::modal.confirmation' => 'phosphor-o-warning',

            // Tables
            'tables::actions.filter' => 'phosphor-o-funnel',
            'tables::actions.group' => 'phosphor-o-rows',
            'tables::actions.enable-reordering' => 'phosphor-o-arrows-down-up',
            'tables::actions.disable-reordering' => 'phosphor-o-check',
            'tables::actions.open-bulk-actions' => 'phosphor-o-dots-three',
            'tables::actions.column-manager' => 'phosphor-o-columns',
            'tables::columns.collapse-button' => 'phosphor-o-caret-down',
            'tables::columns.icon-column.false' => 'phosphor-o-x-circle',
            'tables::columns.icon-column.true' => 'phosphor-o-check-circle',
            'tables::empty-state' => 'phosphor-o-x',
            'tables::filters.remove-all-button' => 'phosphor-o-x',
            'tables::grouping.collapse-button' => 'phosphor-o-caret-down',
            'tables::header-cell.sort-asc-button' => 'phosphor-o-arrow-fat-lines-up',
            'tables::header-cell.sort-button' => 'phosphor-o-arrows-down-up',
            'tables::header-cell.sort-desc-button' => 'phosphor-o-arrow-fat-lines-down',
            'tables::reorder.handle' => 'phosphor-o-dots-three',
            'tables::search-field' => 'phosphor-o-magnifying-glass',

            // Forms - Builder
            'forms::components.builder.actions.clone' => 'phosphor-o-files',
            'forms::components.builder.actions.collapse' => 'phosphor-o-caret-up',
            'forms::components.builder.actions.delete' => 'phosphor-o-trash',
            'forms::components.builder.actions.expand' => 'phosphor-o-caret-down',
            'forms::components.builder.actions.move-down' => 'phosphor-o-arrow-down',
            'forms::components.builder.actions.move-up' => 'phosphor-o-arrow-up',
            'forms::components.builder.actions.reorder' => 'phosphor-o-arrows-down-up',

            // Forms - Repeater
            'forms::components.repeater.actions.clone' => 'phosphor-o-files',
            'forms::components.repeater.actions.collapse' => 'phosphor-o-caret-up',
            'forms::components.repeater.actions.delete' => 'phosphor-o-trash',
            'forms::components.repeater.actions.expand' => 'phosphor-o-caret-down',
            'forms::components.repeater.actions.move-down' => 'phosphor-o-arrow-down',
            'forms::components.repeater.actions.move-up' => 'phosphor-o-arrow-up',
            'forms::components.repeater.actions.reorder' => 'phosphor-o-arrows-down-up',

            // Forms - Key-Value
            'forms::components.key-value.actions.delete' => 'phosphor-o-trash',
            'forms::components.key-value.actions.reorder' => 'phosphor-o-arrows-down-up',

            // Forms - Text Input
            'forms::components.text-input.actions.copy' => 'phosphor-o-clipboard',
            'forms::components.text-input.actions.hide-password' => 'phosphor-o-eye-slash',
            'forms::components.text-input.actions.show-password' => 'phosphor-o-eye',

            // Forms - Select
            'forms::components.select.actions.create-option' => 'phosphor-o-plus',
            'forms::components.select.actions.edit-option' => 'phosphor-o-pencil-simple',

            // Forms - Checkbox List
            'forms::components.checkbox-list.search-field' => 'phosphor-o-magnifying-glass',

            // Forms - Toggle Buttons
            'forms::components.toggle-buttons.boolean.false' => 'phosphor-o-x-circle',
            'forms::components.toggle-buttons.boolean.true' => 'phosphor-o-check-circle',

            // Notifications
            'notifications::database.modal.empty-state' => 'phosphor-o-bell',
            'notifications::notification.close-button' => 'phosphor-o-x',
            'notifications::notification.danger' => 'phosphor-o-x-circle',
            'notifications::notification.info' => 'phosphor-o-info',
            'notifications::notification.success' => 'phosphor-o-check-circle',
            'notifications::notification.warning' => 'phosphor-o-warning',

            // Support - Badges & Modals
            'badge.delete-button' => 'phosphor-o-x',
            'modal.close-button' => 'phosphor-o-x',

            // Support - Breadcrumbs
            'breadcrumbs.separator' => 'phosphor-o-caret-right',
            'breadcrumbs.separator.rtl' => 'phosphor-o-caret-left',

            // Support - Pagination
            'pagination.first-button' => 'phosphor-o-caret-double-left',
            'pagination.first-button.rtl' => 'phosphor-o-caret-double-right',
            'pagination.last-button' => 'phosphor-o-caret-double-right',
            'pagination.last-button.rtl' => 'phosphor-o-caret-double-left',
            'pagination.next-button' => 'phosphor-o-caret-right',
            'pagination.next-button.rtl' => 'phosphor-o-caret-left',
            'pagination.previous-button' => 'phosphor-o-caret-left',
            'pagination.previous-button.rtl' => 'phosphor-o-caret-right',

            // Support - Sections
            'section.collapse-button' => 'phosphor-o-caret-down',
        ]);
    }

    /**
     * Configure GitHub stars count.
     */
    private function configureGitHubStars(): void
    {
        // Share GitHub stars count with the header component
        Facades\View::composer('components.layout.header', function (View $view): void {
            $gitHubService = resolve(GitHubService::class);
            $starsCount = $gitHubService->getStarsCount();
            $formattedStarsCount = $gitHubService->getFormattedStarsCount();

            $view->with([
                'githubStars' => $starsCount,
                'formattedGithubStars' => $formattedStarsCount,
            ]);
        });
    }
}
