<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureModels();
        $this->configureFilament();
    }

    /**
     * Configure the models for the application.
     */
    private function configureModels(): void
    {
        Model::unguard();
        Model::shouldBeStrict(! $this->app->isProduction());

        Relation::enforceMorphMap([
            'team' => Team::class,
            'user' => User::class,
            'people' => People::class,
            'company' => Company::class,
            'opportunity' => Opportunity::class,
            'task' => Task::class,
            'note' => Note::class,
        ]);
    }

    /**
     * Configure Filament.
     */
    private function configureFilament(): void
    {
        $slideOverActions = ['create', 'edit', 'view'];

        Action::configureUsing(function (Action $action) use ($slideOverActions): Action {
            if (in_array($action->getName(), $slideOverActions)) {
                return $action->slideOver();
            }

            return $action;
        });

        TableAction::configureUsing(function (TableAction $action) use ($slideOverActions): TableAction {
            if (in_array($action->getName(), $slideOverActions)) {
                return $action->slideOver();
            }

            return $action;
        });
    }
}
