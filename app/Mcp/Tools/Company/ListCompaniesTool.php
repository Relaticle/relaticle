<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Company;

use App\Actions\Company\ListCompanies;
use App\Enums\PartnerSource;
use App\Http\Resources\V1\CompanyResource;
use App\Mcp\Tools\BaseListTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List companies in the CRM with optional search, portfolio filters, and pagination.')]
#[IsReadOnly]
#[IsIdempotent]
final class ListCompaniesTool extends BaseListTool
{
    protected function actionClass(): string
    {
        return ListCompanies::class;
    }

    protected function resourceClass(): string
    {
        return CompanyResource::class;
    }

    protected function searchFilterName(): string
    {
        return 'name';
    }

    /**
     * @return array<string, mixed>
     */
    protected function additionalSchema(JsonSchema $schema): array
    {
        $sourceValues = implode(', ', array_map(fn (PartnerSource $s): string => $s->value, PartnerSource::cases()));

        return [
            'partner_source' => $schema->string()->description("Filter by acquisition channel. One of: {$sourceValues}."),
            'geography' => $schema->string()->description('Filter by ISO 3166-1 alpha-2 country code (e.g. US, GB, DE).'),
            'is_recurring' => $schema->boolean()->description('Filter to recurring-revenue accounts only when true.'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function additionalFilters(Request $request): array
    {
        return array_filter([
            'partner_source' => $request->get('partner_source'),
            'geography' => $request->get('geography'),
            'is_recurring' => $request->get('is_recurring'),
        ], fn (mixed $v): bool => $v !== null);
    }
}
