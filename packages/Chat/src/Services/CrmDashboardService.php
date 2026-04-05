<?php

declare(strict_types=1);

namespace Relaticle\Chat\Services;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Support\Facades\Cache;

final readonly class CrmDashboardService
{
    /**
     * @return array{
     *     record_counts: array<string, int>,
     *     recent_activity: array<string, int>,
     * }
     */
    public function getSummary(Team $team): array
    {
        $cacheKey = "dashboard_summary_{$team->getKey()}";

        return Cache::remember($cacheKey, 60, fn (): array => [
            'record_counts' => [
                'companies' => Company::query()->count(),
                'people' => People::query()->count(),
                'opportunities' => Opportunity::query()->count(),
                'tasks' => Task::query()->count(),
                'notes' => Note::query()->count(),
            ],
            'recent_activity' => [
                'companies_this_week' => Company::query()->where('created_at', '>=', now()->startOfWeek())->count(),
                'tasks_this_week' => Task::query()->where('created_at', '>=', now()->startOfWeek())->count(),
                'opportunities_this_week' => Opportunity::query()->where('created_at', '>=', now()->startOfWeek())->count(),
            ],
        ]);
    }
}
