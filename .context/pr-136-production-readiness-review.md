# PR #136 Production Readiness Review

**PR:** feat: REST API, MCP server, and team-scoped API tokens
**Scope:** 151 files changed, +9,892 / -267 lines
**Date:** 2026-03-12

---

## Executive Summary

PR #136 introduces a full REST API (JSON:API format), MCP server for AI agent interactions, and team-scoped API tokens. The architecture is sound -- the shared Actions layer, Spatie QueryBuilder integration, and team-scoped middleware are well-designed. However, there are **2 critical**, **8 high**, **10 medium**, and several low-severity issues that must be addressed before production.

The two most urgent blockers:
1. **Token permissions are never enforced** -- the entire ability system is cosmetic
2. **`laravel/mcp` is not a direct production dependency** -- production deploys will crash

---

## CRITICAL Issues (Must Fix Before Merge)

### C1. Token Abilities/Permissions Are Never Enforced

**Impact:** Any token can perform any operation regardless of assigned permissions.
**Files:** All API controllers, all MCP tools, `routes/api.php`, `routes/ai.php`

Tokens are created with granular permissions (`create`, `read`, `update`, `delete`) via the CreateApiToken Livewire component, but **no middleware or code checks these permissions**. There is zero usage of `tokenCan()`, `CheckAbilities`, or `CheckForAnyAbility` middleware anywhere.

A token created with only `read` permission can create, update, and delete records. The permission system is purely cosmetic.

**Fix:** Add Sanctum's `CheckForAnyAbility` middleware to route groups, or check `$request->user()->tokenCan('create')` in each controller/action. Also enforce in MCP tools.

---

### C2. `laravel/mcp` Is Not a Direct Production Dependency

**Impact:** Production deployment with `composer install --no-dev` will crash with class-not-found errors for all MCP functionality.
**File:** `composer.json`

The entire `app/Mcp/` directory and `routes/ai.php` depend on `Laravel\Mcp\*` classes, but `laravel/mcp` is only available transitively through `laravel/boost` (which is in `require-dev`).

**Fix:** Add `"laravel/mcp": "^0.5.1"` to the `require` section of `composer.json`.

---

### C3. Missing Index on `personal_access_tokens.team_id`

**Impact:** Every query filtering tokens by `team_id` will require a sequential scan on PostgreSQL.
**File:** `database/migrations/2026_02_20_212853_add_team_id_to_personal_access_tokens_table.php`

The migration adds `foreignUlid('team_id')` with a foreign key constraint but PostgreSQL does not automatically create an index on the referencing column.

**Fix:** Add `->index()` to the `team_id` column definition.

---

## HIGH Issues (Should Fix Before Merge)

### H1. Mass Assignment Risk via `Model::unguard()`

**Impact:** API users could potentially set any column on models since `$fillable` is disabled globally.
**File:** `app/Providers/AppServiceProvider.php:148`

`Model::unguard()` is called globally. Combined with the API accepting user-controlled `$data` arrays passed directly to `->create($data)` and `->update($data)`, this means API users could theoretically set unauthorized columns. While FormRequest `validated()` limits fields, the safety net of `$fillable` is absent.

**Fix:** Either re-guard models and ensure proper `$fillable` on all models (note: Opportunity and Note have extremely narrow `$fillable` that would break), or ensure all Action classes use `Arr::only($data, [...allowed fields...])` before passing to `create()`/`update()`.

---

### H2. MCP List Tools Mutate Global `request()` State

**Impact:** Under Octane/Swoole or MCP batch calls, request state from one tool leaks into subsequent calls.
**Files:** All `app/Mcp/Tools/*/List*Tool.php` (5 files)

Every MCP list tool calls `request()->merge($query)` to inject filter parameters into the global request object. The PR already shows Octane awareness in `SetApiTeamContext::terminate()`, making this inconsistency notable.

**Fix:** Refactor List actions to accept explicit parameters instead of reading from `request()`. Pass parameters directly rather than mutating global state.

---

### H3. List Actions Coupled to HTTP Request

**Impact:** Actions cannot be reused in non-HTTP contexts (Artisan, queued jobs) without an active request.
**Files:** All `app/Actions/*/List*.php` (5 files)

The List actions directly call `request()->query('per_page')` and `request()->has('cursor')` inside `execute()`. This forces MCP tools to use the `request()->merge()` hack and prevents true reusability.

**Fix:** Accept `$perPage`, `$useCursor`, and filter parameters as explicit method arguments with defaults.

---

### H4. `/api/user` Endpoint Leaks User Data Without Resource

**Impact:** Exposes `current_team_id`, `email_verified_at`, `profile_photo_path`, and all appended attributes.
**File:** `routes/api.php:16-18`

The endpoint returns `$request->user()` directly, bypassing the `UserResource` that exists in the codebase. The `current_team_id` in particular leaks internal team ULIDs.

**Fix:** Use `new UserResource($request->user())` and review which fields `UserResource` exposes.

---

### H5. N+1 Query on `$token->team` in SetApiTeamContext

**Impact:** An extra database query on every single API/MCP request where the token has a `team_id`.
**File:** `app/Http/Middleware/SetApiTeamContext.php:77-78`

Sanctum resolves the token but doesn't eager-load the `team` relationship.

**Fix:** Use `$token->load('team')` or `Team::query()->find($token->team_id)` directly.

---

### H6. No Pagination on Custom Fields Endpoint

**Impact:** A malicious or misconfigured client could retrieve an unbounded result set.
**File:** `app/Http/Controllers/Api/V1/CustomFieldsController.php:44`

The endpoint returns `$query->get()` -- all matching custom fields without pagination or limits.

**Fix:** Add `->paginate()` or at minimum `->limit(100)`.

---

### H7. MCP Schema Resources Query Custom Fields With No Caching

**Impact:** Every MCP client schema read triggers a full database query. MCP clients read all resources at connection startup.
**File:** `app/Mcp/Resources/Concerns/ResolvesEntitySchema.php:17-24`

**Fix:** Cache custom field schema per team/entity-type with a short TTL (60s).

---

### H8. `PersonalAccessToken.team_id` Is Mutable After Creation

**Impact:** Any code path calling `$token->update()` could change the team association, breaking the "permanently scoped" guarantee.
**File:** `app/Models/PersonalAccessToken.php:13-18`

`team_id` is in `$fillable`. Combined with `Model::unguard()`, there's no immutability enforcement.

**Fix:** Remove `team_id` from `$fillable`, use `forceFill()` only at creation. Optionally add an `updating` event to prevent changes.

---

## MEDIUM Issues (Fix Before or Shortly After Merge)

### M1. Octane: `terminate()` Doesn't Reset Web Guard

**File:** `app/Http/Middleware/SetApiTeamContext.php:64-71`

The `terminate()` method resets tenant context and clears booted models, but does NOT reset `auth()->guard('web')->setUser()`. Under Octane, the web guard's user could leak to a subsequent non-API request.

**Fix:** Add `auth()->guard('web')->forgetUser()` in `terminate()`.

---

### M2. Double Authorization in MCP Update/Delete Tools

**Files:** All `app/Mcp/Tools/*/Update*Tool.php` and `Delete*Tool.php` (10 files)

MCP tools call `Gate::authorize()` before calling the action, but the action also calls `abort_unless($user->can(...))`. Authorization is checked twice per operation.

**Fix:** Remove one layer. Either let actions own authorization (and remove from tools) or vice versa.

---

### M3. API Resources Missing FK Attributes

**Files:** `app/Http/Resources/V1/PeopleResource.php`, `OpportunityResource.php`

Resources don't expose `company_id`, `contact_id` as attributes. API consumers must use `?include=company` to discover relationships but can't easily reference by FK. While JSON:API-compliant, this is a friction point.

**Fix:** Consider adding FK IDs to attributes for convenience.

---

### M4. LIKE Queries on Unindexed Columns

**Files:** All List actions using `AllowedFilter::partial('name')`

`AllowedFilter::partial('name')` generates `WHERE name ILIKE '%value%'` on PostgreSQL. The `name`/`title` columns have no index.

**Fix:** Add `pg_trgm` GIN indexes on searchable columns, or switch to prefix-only matching.

---

### M5. CrmOverviewPrompt: 7 Sequential Queries With No Caching

**File:** `app/Mcp/Prompts/CrmOverviewPrompt.php:22-28`

Executes 7 separate queries (5 counts + 2 recent record queries) on every MCP prompt invocation.

**Fix:** Combine counts into a single query or cache with a short TTL.

---

### M6. Rate Limiting May Be Too Low for MCP

**File:** `app/Providers/AppServiceProvider.php:127`

60 requests/minute shared between API and MCP. AI agents using MCP can easily exceed this during active conversations.

**Fix:** Consider separate, higher rate limits for MCP routes.

---

### M7. `CreationSource::API` and `::WEB` Have Same Badge Color

**File:** `app/Enums/CreationSource.php:51-52`

Both return `'info'` -- visually indistinguishable in the Filament UI.

**Fix:** Use a different color for API (e.g., `'primary'` or `'warning'`).

---

### M8. MCP Tool Code Duplication (20 files)

**Files:** All `app/Mcp/Tools/**/*.php`

All 5 List/Create/Update/Delete tools share near-identical boilerplate. A base class per operation type could reduce ~60% of this code.

**Fix:** Extract `BaseListTool`, `BaseCreateTool`, `BaseUpdateTool`, `BaseDeleteTool` with entity-specific config in subclasses.

---

### M9. `ListNotes` Missing Polymorphic Filtering

**File:** `app/Actions/Note/ListNotes.php:24-26`

Notes are polymorphic but no filter exists for `notable_type`/`notable_id`. API consumers can't ask "show me notes for company X."

**Fix:** Add `AllowedFilter::exact('notable_type')` and `AllowedFilter::exact('notable_id')`.

---

### M10. `ListTasksTool` Missing `assigned_to_me` Filter

**File:** `app/Mcp/Tools/Task/ListTasksTool.php`

The action supports `assigned_to_me` but the MCP tool doesn't expose it. Useful for AI agents asking "show me my tasks."

**Fix:** Add the parameter to the tool schema.

---

## LOW Issues (Nice to Have)

| # | Issue | File |
|---|-------|------|
| L1 | `custom-fields` route missing name | `routes/api.php:29` |
| L2 | `CustomFieldsController` uses inline validation instead of FormRequest | `CustomFieldsController.php:25-27` |
| L3 | `PersonalAccessToken` missing `$casts` for `expires_at` | `PersonalAccessToken.php` |
| L4 | `UpdateTask` still imports Filament classes (presentation in action layer) | `UpdateTask.php:7-11` |
| L5 | No data migration for existing tokens (null `team_id`) | Migration file |
| L6 | Token creation + fill/save is not atomic | `CreateApiToken.php:145-154` |
| L7 | `X-Team-Id` validation allows any 26-char alphanumeric, not just valid ULIDs | `SetApiTeamContext.php:84` |
| L8 | Hardcoded `/docs/api` URL in Blade template | `api-tokens.blade.php:9` |
| L9 | Scribe config calls `config('app.url')` at parse time | `config/scribe.php:36` |
| L10 | `FormRequest::authorize()` returns `true` in all 10 classes (could use base class) | All FormRequests |

---

## Test Coverage Gaps

### CRITICAL Test Gaps

| Gap | Why It Matters |
|-----|----------------|
| **Token permission enforcement untested (and unimplemented)** | The entire permission system is non-functional -- no test caught it because there's nothing to test |
| **Token-based team scoping path never exercised** | All tests use `Sanctum::actingAs()` which doesn't set `team_id` -- the real token resolution path is untested |
| **Expired token acceptance untested** | No test verifies expired tokens are rejected |

### HIGH Test Gaps

| Gap | Entities Affected |
|-----|-------------------|
| Soft-delete visibility tests | People, Notes, Tasks, Opportunities (only Companies tested) |
| Filtering tests | Notes, Tasks, Opportunities, People `company_id` |
| Sorting tests | People, Notes, Tasks, Opportunities |
| Disallowed filter/sort rejection | People, Notes, Tasks, Opportunities |
| Pagination tests | People, Notes, Tasks, Opportunities |
| MCP custom field create/update/validation | All entities |
| Relationship include tests beyond `creator` | All entities |
| Mass assignment protection | People, Notes, Tasks, Opportunities (only Companies tested) |

### MEDIUM Test Gaps

| Gap | Details |
|-----|---------|
| Validation boundary tests | Missing for Notes, Tasks, Opportunities |
| Update validation (cross-team FK) | Missing for Opportunities `contact_id` on update |
| Invalid `entity_type` on custom fields endpoint | Never tested |
| `ForceJsonResponse` middleware | Never tested |
| Rate limiting (429 response) | Never tested |
| `SetApiTeamContext.terminate()` cleanup | Never tested |
| MCP `creation_source` verification | Only Companies tested |
| MCP required-field validation | Only Companies tested |
| API JSON:API structure consistency | Only Companies has thorough structural assertions |
| Cursor pagination mode | Never tested |
| Sparse fieldsets (`fields` parameter) | Never tested |
| Cannot delete another user's token | Never tested |
| Non-existent resource returns 404 | Never tested (cross-team returns 404 via scopes, but UUID that doesn't exist is untested) |

---

## Positive Observations

These aspects are well-implemented and should be preserved:

1. **Team scoping architecture** -- Dual-layer protection (global scopes + policy checks) provides strong tenant isolation
2. **FK validation properly scoped** -- `company_id`/`contact_id` use `Rule::exists()->where('team_id', ...)` preventing cross-tenant injection
3. **Spatie QueryBuilder whitelists** -- `allowedFilters()`, `allowedSorts()`, `allowedIncludes()` with explicit allowlists
4. **JSON:API format** -- Proper use of Laravel's `JsonApiResource`
5. **Action layer** -- Clean separation of business logic from controllers and MCP tools
6. **Octane awareness** -- `terminate()` method shows consideration for long-lived processes
7. **Translation file** -- UI strings properly extracted to `lang/en/access-tokens.php`
8. **Comprehensive test suite** -- 106+ tests covering most happy paths

---

## Recommended Fix Priority

### Before Merge (Blockers)
1. C1 -- Enforce token permissions
2. C2 -- Add `laravel/mcp` as production dependency
3. C3 -- Add index on `team_id`
4. H1 -- Address mass assignment risk
5. H4 -- Fix `/api/user` data exposure

### Before Production Release
6. H2/H3 -- Decouple List actions from global request
7. H5 -- Fix N+1 on token team
8. H6 -- Paginate custom fields endpoint
9. H8 -- Make `team_id` immutable on tokens
10. M1 -- Fix Octane web guard leak
11. Fill critical test gaps (token permissions, expiration, team scoping path)

### Fast Follow (Next Sprint)
12. M2-M10 -- Medium issues
13. Fill remaining test gaps
14. L1-L10 -- Low issues
