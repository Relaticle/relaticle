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

#[Description('Simulate the portfolio impact of changing a company\'s concentration percentage. Shows risk band changes, portfolio average delta, and HHI shift.')]
#[IsReadOnly]
#[IsIdempotent]
final class PortfolioWhatIfTool extends Tool
{
    use ChecksTokenAbility;
    use LogsToolInvocation;

    public function schema(JsonSchema $schema): array
    {
        return [
            'company_id' => $schema->string()->description('The company ID to simulate.')->required(),
            'new_concentration' => $schema->number()->description('Hypothetical new concentration percentage (0–100).')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $start = $this->startLog('portfolio_what_if');

        $this->ensureTokenCan('read');

        $validated = $request->validate([
            'company_id' => ['required', 'string'],
            'new_concentration' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $company = Company::query()->find($validated['company_id']);

        if (! $company instanceof Model) {
            return Response::error("Company with ID [{$validated['company_id']}] not found.");
        }

        $impact = resolve(PortfolioRiskContextService::class)->whatIf(
            $company,
            (float) $validated['new_concentration'],
        );

        $this->completeLog('portfolio_what_if', $start);

        return Response::text(json_encode($impact, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
