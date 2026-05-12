<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Company;

use App\Mcp\Tools\Concerns\ChecksTokenAbility;
use App\Mcp\Tools\Concerns\LogsToolInvocation;
use App\Services\Portfolio\PortfolioRiskContextService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Generate a portfolio concentration report with HHI risk index, per-risk-band breakdown, and top-risk accounts.')]
#[IsReadOnly]
#[IsIdempotent]
final class PortfolioConcentrationReportTool extends Tool
{
    use ChecksTokenAbility;
    use LogsToolInvocation;

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        $start = $this->startLog('portfolio_concentration_report');

        $this->ensureTokenCan('read');

        $report = resolve(PortfolioRiskContextService::class)->concentrationReport();

        $this->completeLog('portfolio_concentration_report', $start);

        return Response::text(json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
