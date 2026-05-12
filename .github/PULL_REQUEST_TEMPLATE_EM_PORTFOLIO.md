## Enterprise Movement Portfolio Extension

Adds a portfolio-risk layer to the Company model, surfaced in both the Filament UI and the MCP AI-native layer.

### What changed

**Domain (Phase 1)**
- New nullable columns on `companies`: `partner_source` (enum), `geography` (ISO-2 string), `concentration_percentage` (decimal 0–100), `is_recurring` (boolean)
- `PartnerSource` and `RiskBand` enums with Filament color/icon/label contracts
- `PortfolioMetadata` Spatie Data object + `PortfolioMetadataRules` shared validation helper
- `CountryFlag` support class — 50-country emoji/name registry for Filament selects

**Filament UI (Phase 2)**
- New *Portfolio Health* dashboard at `/portfolio-health` with 5 widgets: KPI stats, concentration bar chart, partner-source donut, geography table, and a ranked risk table with per-row "Explain risk" modal
- `CompanyResource`: portfolio fields added to form, table columns (with risk-colored badges), filters, and the view infolist

**MCP AI layer (Phase 3)**
- Three new read-only MCP tools: `portfolio_concentration_report`, `portfolio_risk_explain` (returns LLM-ready narrative prompt), `portfolio_what_if`
- `PortfolioRiskContextService` shared by both MCP tools and the Filament modal — single source of HHI and ranking logic
- `mcp_tool_invocation_logs` table — append-only team-scoped audit trail (tool name, user, duration)
- `ListCompaniesTool` extended with `partner_source`, `geography`, `is_recurring` filters

**Tests (Phase 4)**
- 30 feature tests in `PortfolioToolsTest` covering all three tools end-to-end: structure, risk band boundaries, delta arithmetic, validation, token abilities, audit logging, and list filters

### Test plan

- [ ] All 189 MCP feature tests pass: `php artisan test --compact tests/Feature/Mcp/`
- [ ] PHPStan: 0 errors (`vendor/bin/phpstan analyse`)
- [ ] Type coverage: 100% (`composer test:type-coverage`)
- [ ] Migrations run clean on a fresh database
- [ ] Portfolio Health dashboard renders at `/[panel]/portfolio-health`
- [ ] Company form shows Portfolio Metadata section
- [ ] "Explain risk" modal populates from live data
- [ ] MCP tools callable via `portfolio_concentration_report`, `portfolio_risk_explain`, `portfolio_what_if`
- [ ] `mcp_tool_invocation_logs` row created after each tool call

🤖 Generated with [Claude Code](https://claude.com/claude-code)
