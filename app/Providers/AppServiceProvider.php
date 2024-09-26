<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        Relation::enforceMorphMap([
            'user' => User::class,
            'people' => People::class,
            'company' => Company::class,
            'opportunity' => Opportunity::class,
            'task' => Task::class,
            'note' => Note::class,
        ]);

        if(App::isProduction()) {
            URL::forceScheme('https');
        }
    }
}
