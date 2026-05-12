<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Mcp\Resources\Concerns\ResolvesEntitySchema;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Description('Schema for companies including available custom fields. Read this before creating or updating companies.')]
#[Uri('relaticle://schema/company')]
#[MimeType('application/json')]
final class CompanySchemaResource extends Resource
{
    use ResolvesEntitySchema;

    public function shouldRegister(): bool
    {
        $token = auth()->user()?->currentAccessToken();
        if (! $token instanceof PersonalAccessToken) {
            return true;
        }
        if (! $token->getKey()) {
            return true;
        }

        return $token->can('read');
    }

    public function handle(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $schema = [
            'entity' => 'company',
            'description' => 'Organizations and businesses tracked in the CRM.',
            'fields' => [
                'name' => ['type' => 'string', 'required' => true],
                'partner_source' => ['type' => 'string', 'nullable' => true, 'description' => 'Acquisition channel. Values: direct, referral_partner, channel_partner, reseller, marketing_inbound, event.'],
                'geography' => ['type' => 'string', 'nullable' => true, 'description' => 'ISO 3166-1 alpha-2 country code (e.g. US, GB, DE).'],
                'concentration_percentage' => ['type' => 'number', 'nullable' => true, 'description' => 'Revenue concentration as a % of total portfolio (0–100). Returned read-only as risk_band (low/medium/high).'],
                'is_recurring' => ['type' => 'boolean', 'default' => false, 'description' => 'Whether the account has recurring revenue.'],
            ],
            'read_only_fields' => [
                'risk_band' => 'Derived from concentration_percentage: low (<10%), medium (10–30%), high (≥30%).',
            ],
            'portfolio_tools' => [
                'portfolio_concentration_report' => 'Full portfolio summary with HHI risk index and per-band breakdowns.',
                'portfolio_risk_explain' => 'Structured risk context for a single company (peer comparison, percentile, narrative prompt).',
                'portfolio_what_if' => 'Simulate the portfolio impact of changing a company\'s concentration.',
            ],
            'custom_fields' => $this->resolveCustomFields($user, 'company'),
            'filterable_fields' => $this->resolveFilterableFields($user, 'company'),
            'relationships' => ['creator', 'accountOwner', 'people', 'opportunities'],
            'aggregate_includes' => [
                'peopleCount' => 'Count of related people',
                'opportunitiesCount' => 'Count of related opportunities',
                'tasksCount' => 'Count of related tasks',
                'notesCount' => 'Count of related notes',
            ],
            'usage' => 'Pass custom field values in the "custom_fields" object using field codes as keys. Use "filter" param in list tools to filter by custom field values with operators (eq, gt, gte, lt, lte, contains, in, has_any). Example: {"name": "Acme", "custom_fields": {"icp": true}}',
        ];

        return Response::text(json_encode($schema, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
