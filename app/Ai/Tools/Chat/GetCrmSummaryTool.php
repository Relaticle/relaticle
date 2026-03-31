<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final class GetCrmSummaryTool implements Tool
{
    public function description(): string
    {
        return 'Get a summary of CRM data: record counts and recent activity.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $summary = [
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
        ];

        return (string) json_encode($summary, JSON_PRETTY_PRINT);
    }
}
