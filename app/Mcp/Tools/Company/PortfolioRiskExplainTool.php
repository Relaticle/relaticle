<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Company;

use App\Mcp\Tools\Concerns\ChecksTokenAbility;
use App\Mcp\Tools\Concerns\LogsToolInvocation;
use App\Models\Company;
use App\Services\Portfolio\PortfolioRiskContextService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Return structured risk context for a single company. Feed the "narrative_prompt" field to an LLM to generate a risk explanation.')]
#[IsReadOnly]
#[IsIdempotent]
final class PortfolioRiskExplainTool extends Tool
{
    use ChecksTokenAbility;
    use LogsToolInvocation;

    public function schema(JsonSchema $schema): array
    {
        return [
            'company_id' => $schema->string()->description('The company ID to explain risk for.')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $start = $this->startLog('portfolio_risk_explain');

        $this->ensureTokenCan('read');

        $validated = $request->validate([
            'company_id' => ['required', 'string'],
        ]);

        $company = Company::query()->find($validated['company_id']);

        if (! $company instanceof Model) {
            return Response::error("Company with ID [{$validated['company_id']}] not found.");
        }

        $context = resolve(PortfolioRiskContextService::class)->riskContext($company);

        $this->completeLog('portfolio_risk_explain', $start);

        return Response::text(json_encode($context, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
