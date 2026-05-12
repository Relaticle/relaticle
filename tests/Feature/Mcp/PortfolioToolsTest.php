<?php

declare(strict_types=1);

use App\Enums\PartnerSource;
use App\Enums\RiskBand;
use App\Mcp\Servers\RelaticleServer;
use App\Mcp\Tools\Company\ListCompaniesTool;
use App\Mcp\Tools\Company\PortfolioConcentrationReportTool;
use App\Mcp\Tools\Company\PortfolioRiskExplainTool;
use App\Mcp\Tools\Company\PortfolioWhatIfTool;
use App\Models\Company;
use App\Models\McpToolInvocationLog;
use App\Models\Scopes\TeamScope;
use App\Models\User;
use App\Services\Portfolio\PortfolioRiskContextService;
use Laravel\Mcp\Server\Testing\TestResponse as McpTestResponse;

mutates(
    PortfolioConcentrationReportTool::class,
    PortfolioRiskExplainTool::class,
    PortfolioWhatIfTool::class,
    PortfolioRiskContextService::class,
    McpToolInvocationLog::class,
);

/** @return array<string, mixed> */
function mcpJson(McpTestResponse $response): array
{
    $fn = Closure::bind(fn () => $this->content(), $response, McpTestResponse::class);
    $data = json_decode(implode('', $fn()), true);

    return is_array($data) ? $data : [];
}

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
});

afterEach(function () {
    Company::clearBootedModels();
});

// ---------------------------------------------------------------------------
// PortfolioConcentrationReportTool
// ---------------------------------------------------------------------------
describe('PortfolioConcentrationReportTool', function () {
    it('returns a concentration report with summary fields', function (): void {
        Company::factory()->recycle([$this->user, $this->team])->create(['concentration_percentage' => 5.0]);
        Company::factory()->recycle([$this->user, $this->team])->create(['concentration_percentage' => 15.0]);
        Company::factory()->recycle([$this->user, $this->team])->create(['concentration_percentage' => 40.0]);

        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioConcentrationReportTool::class)
            ->assertOk()
            ->assertSee('"summary"')
            ->assertSee('"total_accounts"')
            ->assertSee('"hhi"')
            ->assertSee('"by_risk_band"')
            ->assertSee('"top_risks"');
    });

    it('counts accounts by risk band correctly', function (): void {
        Company::factory()->recycle([$this->user, $this->team])->create(['concentration_percentage' => 5.0]);
        Company::factory()->recycle([$this->user, $this->team])->create(['concentration_percentage' => 20.0]);
        Company::factory()->recycle([$this->user, $this->team])->create(['concentration_percentage' => 35.0]);

        $data = mcpJson(
            RelaticleServer::actingAs($this->user)
                ->tool(PortfolioConcentrationReportTool::class)
                ->assertOk()
        );

        expect($data['by_risk_band']['low'])->toBe(1)
            ->and($data['by_risk_band']['medium'])->toBe(1)
            ->and($data['by_risk_band']['high'])->toBe(1);
    });

    it('limits top_risks to 10 entries', function (): void {
        Company::factory(15)->recycle([$this->user, $this->team])->create();

        $data = mcpJson(
            RelaticleServer::actingAs($this->user)
                ->tool(PortfolioConcentrationReportTool::class)
                ->assertOk()
        );

        expect($data['top_risks'])->toHaveCount(10);
    });

    it('excludes companies without concentration_percentage', function (): void {
        Company::factory()->recycle([$this->user, $this->team])->create(['concentration_percentage' => null]);
        Company::factory()->recycle([$this->user, $this->team])->create(['concentration_percentage' => 25.0]);

        $data = mcpJson(
            RelaticleServer::actingAs($this->user)
                ->tool(PortfolioConcentrationReportTool::class)
                ->assertOk()
        );

        expect($data['summary']['accounts_with_concentration'])->toBe(1);
    });

    it('requires read token ability', function (): void {
        $token = $this->user->createToken('test', ['create']);
        $this->user->withAccessToken($token->accessToken);

        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioConcentrationReportTool::class)
            ->assertHasErrors(['Invalid ability provided.']);
    });

    it('logs the tool invocation', function (): void {
        Company::factory()->recycle([$this->user, $this->team])->create();

        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioConcentrationReportTool::class)
            ->assertOk();

        expect(McpToolInvocationLog::query()->where('tool_name', 'portfolio_concentration_report')->exists())->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// PortfolioRiskExplainTool
// ---------------------------------------------------------------------------
describe('PortfolioRiskExplainTool', function () {
    it('returns structured risk context for a company', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create([
            'name' => 'Risky Corp',
            'concentration_percentage' => 35.0,
        ]);

        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioRiskExplainTool::class, ['company_id' => $company->id])
            ->assertOk()
            ->assertSee('"company"')
            ->assertSee('"portfolio_context"')
            ->assertSee('"narrative_prompt"')
            ->assertSee('Risky Corp');
    });

    it('returns high risk band for >= 30% concentration', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create([
            'concentration_percentage' => 50.0,
        ]);

        $data = mcpJson(
            RelaticleServer::actingAs($this->user)
                ->tool(PortfolioRiskExplainTool::class, ['company_id' => $company->id])
                ->assertOk()
        );

        expect($data['company']['risk_band'])->toBe(RiskBand::High->value);
    });

    it('returns medium risk band for 10-29.99% concentration', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create([
            'concentration_percentage' => 15.0,
        ]);

        $data = mcpJson(
            RelaticleServer::actingAs($this->user)
                ->tool(PortfolioRiskExplainTool::class, ['company_id' => $company->id])
                ->assertOk()
        );

        expect($data['company']['risk_band'])->toBe(RiskBand::Medium->value);
    });

    it('returns low risk band for < 10% concentration', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create([
            'concentration_percentage' => 5.0,
        ]);

        $data = mcpJson(
            RelaticleServer::actingAs($this->user)
                ->tool(PortfolioRiskExplainTool::class, ['company_id' => $company->id])
                ->assertOk()
        );

        expect($data['company']['risk_band'])->toBe(RiskBand::Low->value);
    });

    it('assigns rank 1 to the highest concentration account', function (): void {
        $topCompany = Company::factory()->recycle([$this->user, $this->team])->create([
            'concentration_percentage' => 50.0,
        ]);
        Company::factory()->recycle([$this->user, $this->team])->create(['concentration_percentage' => 20.0]);
        Company::factory()->recycle([$this->user, $this->team])->create(['concentration_percentage' => 10.0]);

        $data = mcpJson(
            RelaticleServer::actingAs($this->user)
                ->tool(PortfolioRiskExplainTool::class, ['company_id' => $topCompany->id])
                ->assertOk()
        );

        expect($data['portfolio_context']['concentration_rank'])->toBe(1);
    });

    it('returns error for non-existent company', function (): void {
        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioRiskExplainTool::class, ['company_id' => 'non-existent-id'])
            ->assertHasErrors(['not found']);
    });

    it('requires company_id parameter', function (): void {
        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioRiskExplainTool::class, [])
            ->assertHasErrors(['company id']);
    });

    it('requires read token ability', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create();
        $token = $this->user->createToken('test', ['create']);
        $this->user->withAccessToken($token->accessToken);

        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioRiskExplainTool::class, ['company_id' => $company->id])
            ->assertHasErrors(['Invalid ability provided.']);
    });

    it('logs the tool invocation', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create();

        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioRiskExplainTool::class, ['company_id' => $company->id])
            ->assertOk();

        expect(McpToolInvocationLog::query()->where('tool_name', 'portfolio_risk_explain')->exists())->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// PortfolioWhatIfTool
// ---------------------------------------------------------------------------
describe('PortfolioWhatIfTool', function () {
    it('returns what-if analysis with current, projected, and delta fields', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create([
            'concentration_percentage' => 20.0,
        ]);

        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioWhatIfTool::class, [
                'company_id' => $company->id,
                'new_concentration' => 35.0,
            ])
            ->assertOk()
            ->assertSee('"current"')
            ->assertSee('"projected"')
            ->assertSee('"delta"')
            ->assertSee('"interpretation"');
    });

    it('detects risk band change from medium to high', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create([
            'concentration_percentage' => 20.0,
        ]);

        $data = mcpJson(
            RelaticleServer::actingAs($this->user)
                ->tool(PortfolioWhatIfTool::class, [
                    'company_id' => $company->id,
                    'new_concentration' => 35.0,
                ])
                ->assertOk()
        );

        expect($data['delta']['risk_band_changed'])->toBeTrue()
            ->and($data['current']['risk_band'])->toBe(RiskBand::Medium->value)
            ->and($data['projected']['risk_band'])->toBe(RiskBand::High->value);
    });

    it('reports no band change when staying in the same risk band', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create([
            'concentration_percentage' => 12.0,
        ]);

        $data = mcpJson(
            RelaticleServer::actingAs($this->user)
                ->tool(PortfolioWhatIfTool::class, [
                    'company_id' => $company->id,
                    'new_concentration' => 25.0,
                ])
                ->assertOk()
        );

        expect($data['delta']['risk_band_changed'])->toBeFalse();
    });

    it('correctly computes concentration delta', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create([
            'concentration_percentage' => 10.0,
        ]);

        $data = mcpJson(
            RelaticleServer::actingAs($this->user)
                ->tool(PortfolioWhatIfTool::class, [
                    'company_id' => $company->id,
                    'new_concentration' => 15.0,
                ])
                ->assertOk()
        );

        expect((float) $data['delta']['concentration_change'])->toBe(5.0);
    });

    it('returns error for non-existent company', function (): void {
        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioWhatIfTool::class, [
                'company_id' => 'non-existent-id',
                'new_concentration' => 25.0,
            ])
            ->assertHasErrors(['not found']);
    });

    it('rejects new_concentration above 100', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create();

        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioWhatIfTool::class, [
                'company_id' => $company->id,
                'new_concentration' => 101.0,
            ])
            ->assertHasErrors(['new concentration']);
    });

    it('rejects new_concentration below 0', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create();

        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioWhatIfTool::class, [
                'company_id' => $company->id,
                'new_concentration' => -1.0,
            ])
            ->assertHasErrors(['new concentration']);
    });

    it('requires both company_id and new_concentration parameters', function (): void {
        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioWhatIfTool::class, [])
            ->assertHasErrors(['company id']);
    });

    it('requires read token ability', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create();
        $token = $this->user->createToken('test', ['create']);
        $this->user->withAccessToken($token->accessToken);

        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioWhatIfTool::class, [
                'company_id' => $company->id,
                'new_concentration' => 25.0,
            ])
            ->assertHasErrors(['Invalid ability provided.']);
    });

    it('logs the tool invocation', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create();

        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioWhatIfTool::class, [
                'company_id' => $company->id,
                'new_concentration' => 25.0,
            ])
            ->assertOk();

        expect(McpToolInvocationLog::query()->where('tool_name', 'portfolio_what_if')->exists())->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// ListCompaniesTool portfolio filters
// ---------------------------------------------------------------------------
describe('ListCompaniesTool portfolio filters', function () {
    beforeEach(function () {
        Company::addGlobalScope(new TeamScope);
    });

    it('filters by partner_source', function (): void {
        Company::factory()->recycle([$this->user, $this->team])->create([
            'name' => 'Direct Company',
            'partner_source' => PartnerSource::Direct,
        ]);
        Company::factory()->recycle([$this->user, $this->team])->create([
            'name' => 'Referral Company',
            'partner_source' => PartnerSource::ReferralPartner,
        ]);

        RelaticleServer::actingAs($this->user)
            ->tool(ListCompaniesTool::class, [
                'partner_source' => PartnerSource::Direct->value,
            ])
            ->assertOk()
            ->assertSee('Direct Company')
            ->assertDontSee('Referral Company');
    });

    it('filters by geography', function (): void {
        Company::factory()->recycle([$this->user, $this->team])->create([
            'name' => 'US Company',
            'geography' => 'US',
        ]);
        Company::factory()->recycle([$this->user, $this->team])->create([
            'name' => 'GB Company',
            'geography' => 'GB',
        ]);

        RelaticleServer::actingAs($this->user)
            ->tool(ListCompaniesTool::class, [
                'geography' => 'US',
            ])
            ->assertOk()
            ->assertSee('US Company')
            ->assertDontSee('GB Company');
    });

    it('filters by is_recurring true', function (): void {
        Company::factory()->recycle([$this->user, $this->team])->create([
            'name' => 'Recurring Company',
            'is_recurring' => true,
        ]);
        Company::factory()->recycle([$this->user, $this->team])->create([
            'name' => 'One-Off Company',
            'is_recurring' => false,
        ]);

        RelaticleServer::actingAs($this->user)
            ->tool(ListCompaniesTool::class, [
                'is_recurring' => true,
            ])
            ->assertOk()
            ->assertSee('Recurring Company')
            ->assertDontSee('One-Off Company');
    });
});

// ---------------------------------------------------------------------------
// Audit logging
// ---------------------------------------------------------------------------
describe('McpToolInvocationLog audit logging', function () {
    it('records team_id and user_id on each log entry', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create();

        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioRiskExplainTool::class, ['company_id' => $company->id])
            ->assertOk();

        $log = McpToolInvocationLog::query()->where('tool_name', 'portfolio_risk_explain')->first();

        expect($log)->not->toBeNull()
            ->and($log->team_id)->toBe($this->team->id)
            ->and($log->user_id)->toBe($this->user->id);
    });

    it('records a non-negative duration_ms', function (): void {
        $company = Company::factory()->recycle([$this->user, $this->team])->create();

        RelaticleServer::actingAs($this->user)
            ->tool(PortfolioRiskExplainTool::class, ['company_id' => $company->id])
            ->assertOk();

        $log = McpToolInvocationLog::query()->where('tool_name', 'portfolio_risk_explain')->first();

        expect($log->duration_ms)->toBeGreaterThanOrEqual(0);
    });
});
