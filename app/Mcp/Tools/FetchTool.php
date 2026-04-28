<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ChecksTokenAbility;
use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Fetch a single CRM record by its canonical URL. Pair with the search tool for ChatGPT Company Knowledge citations.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
final class FetchTool extends Tool
{
    use ChecksTokenAbility;

    /** @var array<string, array{0: string, 1: class-string<Model>}> */
    private const SEGMENT_MAP = [
        'companies' => ['company', Company::class],
        'people' => ['person', People::class],
        'opportunities' => ['opportunity', Opportunity::class],
        'tasks' => ['task', Task::class],
        'notes' => ['note', Note::class],
    ];

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->description('Canonical record URL produced by the search tool.')->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->ensureTokenCan('read');

        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'url' => ['required', 'url'],
        ]);

        $path = parse_url((string) $validated['url'], PHP_URL_PATH) ?: '';
        $segments = array_values(array_filter(explode('/', $path)));

        if (count($segments) < 3 || $segments[0] !== 'app' || ! isset(self::SEGMENT_MAP[$segments[1]])) {
            return Response::error("URL [{$validated['url']}] is not a recognized record URL.");
        }

        [$type, $modelClass] = self::SEGMENT_MAP[$segments[1]];
        $id = $segments[2];

        /** @var class-string<Model> $modelClass */
        $model = $modelClass::query()->find($id);

        if (! $model instanceof Model) {
            return Response::error("Record [{$id}] not found.");
        }

        if ($user->cannot('view', $model)) {
            return Response::error('You do not have permission to view this record.');
        }

        return Response::structured([
            'type' => $type,
            'url' => $validated['url'],
            'data' => $model->loadMissing('customFieldValues.customField.options')->toArray(),
        ]);
    }
}
