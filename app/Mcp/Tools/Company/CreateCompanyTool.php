<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Company;

use App\Actions\Company\CreateCompany;
use App\Enums\PartnerSource;
use App\Http\Resources\V1\CompanyResource;
use App\Mcp\Tools\BaseCreateTool;
use App\Models\User;
use App\Rules\PortfolioMetadataRules;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a new company in the CRM. Use the crm-schema resource to discover available custom fields.')]
final class CreateCompanyTool extends BaseCreateTool
{
    protected function actionClass(): string
    {
        return CreateCompany::class;
    }

    protected function resourceClass(): string
    {
        return CompanyResource::class;
    }

    protected function entityType(): string
    {
        return 'company';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        $partnerValues = array_map(fn (PartnerSource $s): string => $s->value, PartnerSource::cases());

        return [
            'name' => $schema->string()->description('The company name.')->required(),
            'partner_source' => $schema->string()->description('Acquisition channel. One of: '.implode(', ', $partnerValues).'.'),
            'geography' => $schema->string()->description('ISO 3166-1 alpha-2 country code (e.g. US, GB, DE).'),
            'concentration_percentage' => $schema->number()->description('Revenue concentration as a percentage of total portfolio (0–100).'),
            'is_recurring' => $schema->boolean()->description('Whether this account has recurring revenue.'),
        ];
    }

    protected function entityRules(User $user): array
    {
        return array_merge(
            ['name' => ['required', 'string', 'max:255']],
            (new PortfolioMetadataRules)->toRules(),
        );
    }
}
