<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat\People;

use App\Actions\People\ListPeople;
use App\Ai\Tools\Chat\BaseReadListTool;
use App\Http\Resources\V1\PeopleResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;

final class ListPeopleTool extends BaseReadListTool
{
    public function description(): string
    {
        return 'List people/contacts in the CRM with optional search and filters.';
    }

    protected function actionClass(): string
    {
        return ListPeople::class;
    }

    protected function resourceClass(): string
    {
        return PeopleResource::class;
    }

    protected function searchFilterName(): string
    {
        return 'name';
    }

    /** @return array<string, mixed> */
    protected function additionalSchema(JsonSchema $schema): array
    {
        return [
            'company_id' => $schema->string()->description('Filter by company ID.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function additionalFilters(Request $request): array
    {
        return array_filter([
            'company_id' => $request['company_id'] ?? null,
        ]);
    }
}
