<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ChecksTokenAbility;
use App\Mcp\Tools\Concerns\SerializesRelatedModels;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

abstract class BaseListTool extends Tool
{
    use ChecksTokenAbility;
    use SerializesRelatedModels;

    /** @return class-string */
    abstract protected function actionClass(): string;

    /** @return class-string<JsonResource> */
    abstract protected function resourceClass(): string;

    abstract protected function searchFilterName(): string;

    /**
     * @return array<string, mixed>
     */
    protected function additionalSchema(JsonSchema $schema): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function additionalFilters(Request $request): array
    {
        return [];
    }

    public function schema(JsonSchema $schema): array
    {
        return array_merge(
            ['search' => $schema->string()->description("Search by {$this->searchFilterName()}.")],
            $this->additionalSchema($schema),
            [
                'filter' => $schema->object()->description('Filter by custom field values. Keys are field codes, values are operator objects (eq, gt, gte, lt, lte, contains, in, has_any).'),
                'sort' => $schema->object()->description('Sort by field. Properties: field (string), direction (asc|desc).'),
                'include' => $schema->array()->description('Related records to expand in response.'),
                'per_page' => $schema->integer()->description('Results per page (default 15, max 100).')->default(15),
                'page' => $schema->integer()->description('Page number.')->default(1),
            ],
        );
    }

    public function handle(Request $request): Response
    {
        $this->ensureTokenCan('read');

        /** @var User $user */
        $user = auth()->user();

        $httpRequest = $this->buildHttpRequest($request);

        $action = app()->make($this->actionClass());
        $results = $action->execute(
            user: $user,
            perPage: (int) $request->get('per_page', 15),
            page: $request->get('page') ? (int) $request->get('page') : null,
            request: $httpRequest,
        );

        /** @var class-string<JsonResource> $resourceClass */
        $resourceClass = $this->resourceClass();

        $collection = $resourceClass::collection($results);

        /** @var array<int|string, mixed> $decoded */
        $decoded = json_decode($collection->toJson(JSON_PRETTY_PRINT), true);
        $items = $decoded['data'] ?? array_values($decoded);

        $relationshipMap = null;

        foreach ($items as $index => $item) {
            $resultItem = $results[$index] ?? null;

            if ($resultItem === null) {
                continue;
            }

            $model = $resultItem instanceof JsonResource ? $resultItem->resource : $resultItem;

            if (! $model instanceof Model) {
                continue;
            }

            foreach ($model->getRelations() as $relation => $relatedData) {
                if ($relation === 'customFieldValues') {
                    continue;
                }

                $relationshipMap ??= $this->resolveRelationshipMap($resourceClass, $model);

                $items[$index][$relation] = $this->serializeRelation($model, $relation, $relationshipMap);
            }
        }

        $response = ['data' => $items];

        if ($results instanceof LengthAwarePaginator) {
            $response['meta'] = [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ];
        }

        return Response::text((string) json_encode($response, JSON_PRETTY_PRINT));
    }

    private function buildHttpRequest(Request $mcpRequest): HttpRequest
    {
        $input = [];

        $nativeFilters = array_filter(array_merge(
            [$this->searchFilterName() => $mcpRequest->get('search')],
            $this->additionalFilters($mcpRequest),
        ));

        if ($nativeFilters !== []) {
            $input['filter'] = $nativeFilters;
        }

        $customFieldFilters = $mcpRequest->get('filter');

        if (is_array($customFieldFilters) && $customFieldFilters !== []) {
            $input['filter']['custom_fields'] = $customFieldFilters;
        }

        $sort = $mcpRequest->get('sort');

        if (is_array($sort) && isset($sort['field'])) {
            $direction = ($sort['direction'] ?? 'asc') === 'desc' ? '-' : '';
            $input['sort'] = $direction.$sort['field'];
        }

        $include = $mcpRequest->get('include');

        if (is_array($include) && $include !== []) {
            $input['include'] = implode(',', $include);
        }

        return new HttpRequest($input);
    }
}
