# Enterprise Movement Portfolio Extension

**Date:** 2026-05-12
**Branch:** feat/em-portfolio-extension
**Status:** Implementation complete (Phases 1–4)

---

## Goal

Add a portfolio-risk layer to Relaticle's Company model so enterprise account managers can see concentration risk, diversity metrics, and AI-ready risk context without leaving the CRM or writing custom reports.

---

## What was built

### Phase 1 — Domain fields

Four new nullable columns on `companies`:

| Column | Type | Purpose |
|--------|------|---------|
| `partner_source` | enum | Acquisition channel (Direct, Referral Partner, Channel Partner, Reseller, Marketing Inbound, Event) |
| `geography` | string(2) | ISO 3166-1 alpha-2 country code |
| `concentration_percentage` | decimal(5,2) | Revenue concentration 0–100 |
| `is_recurring` | boolean | Recurring-revenue flag (default false) |

Supporting additions:
- `App\Enums\PartnerSource` — backed string enum with Filament color/icon/label contracts
- `App\Enums\RiskBand` — Low (<10%), Medium (10–29.99%), High (≥30%)
- `App\Data\PortfolioMetadata` — Spatie Laravel Data object for typed access
- `App\Rules\PortfolioMetadataRules` — validation helper reused across MCP + Filament
- `App\Support\CountryFlag` — 50-country emoji + name registry with `options()` helper for Filament selects

### Phase 2 — Filament UI

**Portfolio Health dashboard** (`/portfolio-health`):
- 5 widgets in a 3-column lg grid
- `PortfolioStatsWidget` — KPI cards: total accounts, avg concentration, HHI index, high-risk count
- `ConcentrationDistributionWidget` — bar chart of concentration percentages
- `PartnerSourceWidget` — donut chart of acquisition channel mix
- `GeographyDistributionWidget` — table of top geographies by account count
- `ConcentrationRiskTableWidget` — ranked table of top-10 high-concentration accounts with per-row "Explain risk" modal action

**CompanyResource updates**:
- Form: new "Portfolio Metadata" section with partner_source, geography (flag picker), concentration_percentage (live risk-band hint), is_recurring toggle
- Table: badge columns for partner_source, geography, concentration_percentage (risk-colored), is_recurring icon
- Filters: SelectFilter for partner_source and geography, toggle filter for is_recurring
- ViewCompany infolist: "Portfolio Metadata" section in the right-hand column

### Phase 3 — MCP AI-native layer

**Three new MCP tools** registered in `RelaticleServer`:

| Tool | Key inputs | Returns |
|------|-----------|---------|
| `portfolio_concentration_report` | none | HHI, by-risk-band counts, by-partner-source totals, top-10 risks |
| `portfolio_risk_explain` | `company_id` | Company snapshot + portfolio context + narrative prompt for LLM |
| `portfolio_what_if` | `company_id`, `new_concentration` | Current vs projected risk band, avg delta, HHI delta |

All three: `#[IsReadOnly]`, `#[IsIdempotent]`, require `read` token ability.

**`PortfolioRiskContextService`** — shared service consumed by both MCP tools and the Filament widget's modal action. Contains:
- `concentrationReport()` — portfolio-level aggregates
- `riskContext(Company)` — per-company risk context with portfolio percentile
- `whatIf(Company, float)` — what-if impact calculation

**Audit logging** — `mcp_tool_invocation_logs` table (team-scoped, ULID, append-only). Every tool invocation records `team_id`, `user_id`, `tool_name`, `duration_ms`. The `LogsToolInvocation` trait provides `startLog()` / `completeLog()` timing helpers.

**`ListCompaniesTool` extended** with portfolio filters via `additionalSchema()` / `additionalFilters()`:
- `partner_source` — exact match by acquisition channel
- `geography` — exact match by ISO country code
- `is_recurring` — boolean filter

### Phase 4 — Tests

`tests/Feature/Mcp/PortfolioToolsTest.php` — 30 feature tests covering:
- Concentration report structure, risk band counts, top-10 limit, null exclusion
- Risk explain: risk band boundaries, concentration ranking, validation, token abilities
- What-if: band change detection, no-change case, delta arithmetic, validation
- List filter tests: partner_source, geography, is_recurring all filtered through Spatie QueryBuilder
- Audit log: team_id, user_id, duration_ms correctly recorded

---

## Architecture decisions

**Single service for MCP + Filament** — `PortfolioRiskContextService` is injected into both the MCP tools and the Filament widget modal. This avoids duplicating the HHI and ranking logic and keeps the domain in one place.

**HHI as portfolio risk index** — Herfindahl-Hirschman Index (sum of squared share fractions). Thresholds: <0.01 highly diversified, <0.15 moderately diversified, <0.25 moderately concentrated, ≥0.25 highly concentrated. Standard economics metric; interpretable to account managers.

**`narrative_prompt` field** — `riskContext()` returns a pre-formatted English prompt string alongside the structured data. AI callers can pipe it directly to a chat model without any prompt-engineering overhead on their side.

**Append-only audit log** — `UPDATED_AT = null`, no factory, team-scoped via `HasTeam`. Gives compliance teams a tamper-resistant record of which AI agents queried risk data and when.

**RiskBand boundaries** — Low <10%, Medium 10–29.99%, High ≥30%. These match the CSS classes used in Filament badge colors and the what-if tool's match expression, keeping a single source of truth.

---

## Files changed

```
app/
  Data/PortfolioMetadata.php
  Enums/PartnerSource.php
  Enums/RiskBand.php
  Filament/Pages/PortfolioHealth.php
  Filament/Resources/CompanyResource.php
  Filament/Resources/CompanyResource/Pages/ViewCompany.php
  Filament/Widgets/ConcentrationDistributionWidget.php
  Filament/Widgets/ConcentrationRiskTableWidget.php
  Filament/Widgets/GeographyDistributionWidget.php
  Filament/Widgets/PartnerSourceWidget.php
  Filament/Widgets/PortfolioStatsWidget.php
  Mcp/Resources/CompanySchemaResource.php
  Mcp/Servers/RelaticleServer.php
  Mcp/Tools/Company/ListCompaniesTool.php
  Mcp/Tools/Company/PortfolioConcentrationReportTool.php
  Mcp/Tools/Company/PortfolioRiskExplainTool.php
  Mcp/Tools/Company/PortfolioWhatIfTool.php
  Mcp/Tools/Concerns/LogsToolInvocation.php
  Models/Company.php
  Models/McpToolInvocationLog.php
  Rules/PortfolioMetadataRules.php
  Services/Portfolio/PortfolioRiskContextService.php
  Support/CountryFlag.php
database/migrations/
  *_add_portfolio_metadata_to_companies_table.php
  *_create_mcp_tool_invocation_logs_table.php
resources/views/filament/widgets/
  risk-explain-modal.blade.php
tests/Feature/Mcp/
  PortfolioToolsTest.php
```
