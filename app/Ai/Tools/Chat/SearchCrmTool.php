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

final class SearchCrmTool implements Tool
{
    public function description(): string
    {
        return 'Search across all CRM entity types (companies, people, opportunities, tasks, notes) by keyword.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The search keyword.')->required(),
            'limit' => $schema->integer()->description('Max results per entity type (default 5).')->default(5),
        ];
    }

    public function handle(Request $request): string
    {
        $query = (string) $request->string('query');
        $limit = min((int) ($request['limit'] ?? 5), 10);

        $results = [
            'companies' => Company::query()
                ->where('name', 'ilike', "%{$query}%")
                ->limit($limit)
                ->get(['id', 'name', 'created_at'])
                ->toArray(),
            'people' => People::query()
                ->where('name', 'ilike', "%{$query}%")
                ->limit($limit)
                ->get(['id', 'name', 'company_id', 'created_at'])
                ->toArray(),
            'opportunities' => Opportunity::query()
                ->where('name', 'ilike', "%{$query}%")
                ->limit($limit)
                ->get(['id', 'name', 'company_id', 'created_at'])
                ->toArray(),
            'tasks' => Task::query()
                ->where('title', 'ilike', "%{$query}%")
                ->limit($limit)
                ->get(['id', 'title', 'created_at'])
                ->toArray(),
            'notes' => Note::query()
                ->where('title', 'ilike', "%{$query}%")
                ->limit($limit)
                ->get(['id', 'title', 'created_at'])
                ->toArray(),
        ];

        return (string) json_encode($results, JSON_PRETTY_PRINT);
    }
}
