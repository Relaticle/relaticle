<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\DeleteAction as TableDeleteAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();

        Relation::enforceMorphMap([
            'user' => User::class,
            'people' => People::class,
            'company' => Company::class,
            'opportunity' => Opportunity::class,
            'task' => Task::class,
            'note' => Note::class,
        ]);

        if (App::isProduction()) {
            URL::forceScheme('https');
        }

        $this->configureFilament();
    }

    /**
     * Configure Filament.
     */
    private function configureFilament(): void
    {
        Action::configureUsing(function (Action $action): Action {
            if (! $action instanceof DeleteAction && $action->getName() !== 'delete') {
                return $action->slideOver();
            }

            return $action;
        });

        TableAction::configureUsing(function (TableAction $action): TableAction {
            if (! $action instanceof TableDeleteAction && $action->getName() !== 'delete' && $action->getName() !== 'openFilters') {
                return $action->slideOver();
            }

            return $action;
        });
    }
}
