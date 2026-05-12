<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Company;

use App\Actions\Company\UpdateCompany;
use App\Enums\PartnerSource;
use App\Http\Resources\V1\CompanyResource;
use App\Mcp\Tools\BaseUpdateTool;
use App\Models\Company;
use App\Models\User;
use App\Rules\PortfolioMetadataRules;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update an existing company in the CRM. Use the crm-schema resource to discover available custom fields.')]
#[IsIdempotent]
final class UpdateCompanyTool extends BaseUpdateTool
{
    protected function modelClass(): string
    {
        return Company::class;
    }

    protected function actionClass(): string
    {
        return UpdateCompany::class;
    }

    protected function resourceClass(): string
    {
        return CompanyResource::class;
    }

    protected function entityType(): string
    {
        return 'company';
    }

    protected function entityLabel(): string
    {
        return 'company';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        $partnerValues = array_map(fn (PartnerSource $s): string => $s->value, PartnerSource::cases());

        return [
            'name' => $schema->string()->description('The company name.'),
            'partner_source' => $schema->string()->description('Acquisition channel. One of: '.implode(', ', $partnerValues).'.'),
            'geography' => $schema->string()->description('ISO 3166-1 alpha-2 country code (e.g. US, GB, DE).'),
            'concentration_percentage' => $schema->number()->description('Revenue concentration as a percentage of total portfolio (0–100).'),
            'is_recurring' => $schema->boolean()->description('Whether this account has recurring revenue.'),
        ];
    }

    protected function entityRules(User $user): array
    {
        return array_merge(
            ['name' => ['sometimes', 'string', 'max:255']],
            (new PortfolioMetadataRules)->toRules(isUpdate: true),
        );
    }
}
