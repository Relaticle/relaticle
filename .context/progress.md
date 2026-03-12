## Codebase Patterns

- **Testing with real tokens**: Use `$user->createToken('name', ['ability'])->plainTextToken` with `$this->withToken($token)` for API tests that need ability enforcement. `Sanctum::actingAs()` in v4 creates Mockery mocks that don't work with `instanceof PersonalAccessToken` checks.
- **MCP testing with tokens**: Use `$user->createToken('name', ['ability'])` then `$user->withAccessToken($token->accessToken)` before `RelaticleServer::actingAs($user)` to set a real token on the user for MCP tool tests.
- **Token detection pattern**: Check `$token instanceof PersonalAccessToken && $token->getKey()` to only enforce abilities on real persisted tokens. This skips TransientToken (session auth), Mockery mocks (tests), and null tokens.
- **Decoupled List actions**: List actions accept explicit `$perPage`, `$useCursor`, `$filters`, `$page`, and optional `$request` parameters. API controllers pass `request: $request` for full QueryBuilder integration; MCP tools pass explicit `filters:` array (no `request()->merge()`). When no `$request` is provided, a synthetic `new Request(['filter' => $filters])` is built for Spatie QueryBuilder.
- **Creating tokens with team_id**: Use `$user->tokens()->create(['name' => ..., 'token' => hash('sha256', $raw), 'abilities' => [...], 'team_id' => $team->id])` then compose `"{$token->id}|{$raw}"` for the plain text token. This is necessary because `createToken()` doesn't accept `team_id` directly.

## US-001: Enforce Sanctum token abilities on API and MCP routes
- Created `EnsureTokenHasAbility` middleware that maps HTTP methods to required abilities (GET->read, POST->create, PUT/PATCH->update, DELETE->delete)
- Applied middleware to API v1 route group in `routes/api.php`
- Created `ChecksTokenAbility` trait with `ensureTokenCan()` method for MCP tools
- Added ability checks to all 20 MCP tools (5 List/read, 5 Create/create, 5 Update/update, 5 Delete/delete)
- Wildcard `*` ability grants full access (built into Sanctum's `PersonalAccessToken::can()`)
- 33 new tests covering all permission boundaries for API and MCP

### Files changed
- `app/Http/Middleware/EnsureTokenHasAbility.php` (new)
- `app/Mcp/Tools/Concerns/ChecksTokenAbility.php` (new)
- `routes/api.php` (added middleware)
- `app/Mcp/Tools/Company/{List,Create,Update,Delete}CompanyTool.php`
- `app/Mcp/Tools/People/{List,Create,Update,Delete}PeopleTool.php`
- `app/Mcp/Tools/Opportunity/{List,Create,Update,Delete}OpportunityTool.php`
- `app/Mcp/Tools/Task/{List,Create,Update,Delete}TaskTool.php`
- `app/Mcp/Tools/Note/{List,Create,Update,Delete}NoteTool.php`
- `tests/Feature/Api/V1/TokenAbilitiesApiTest.php` (new)
- `tests/Feature/Mcp/TokenAbilitiesMcpTest.php` (new)

### Learnings for future iterations:
- `Sanctum::actingAs($user)` in v4 defaults to `[]` abilities (not `['*']`), creating Mockery mocks with `shouldIgnoreMissing(false)` that return `false` for all `can()` calls
- Mockery mocks pass `instanceof` checks for the mocked class, so you need `$token->getKey()` (returns `false` on mocks) to distinguish real vs mock tokens
- Pre-existing failures in `ResolvesEntitySchema.php` (`toCollection()` on null) and `CustomFieldsApiTest` -- not related to this work
- MCP test infrastructure (`RelaticleServer::actingAs`) bypasses middleware -- ability checking must be done in tool handle() methods
- `MissingAbilityException` from Sanctum renders as 403 automatically in API routes; in MCP tools it propagates as an exception (tests use `->throws()`)

## US-002: Fix production dependency and migration issues
- Moved `laravel/mcp` from transitive dev dependency to explicit `require` in `composer.json` (`"^0.5"`)
- Added `->index()` on `team_id` column in `personal_access_tokens` migration (PostgreSQL doesn't auto-index FK referencing columns)
- Added `updating` event on `PersonalAccessToken` to prevent `team_id` changes after initial assignment (allows null->value, blocks value->different_value)
- Added `.context` to `.gitignore`
- 4 new tests covering team_id immutability: set-when-null, block-change, block-nullify, allow-other-attributes

### Files changed
- `composer.json` (added `laravel/mcp` to `require`)
- `database/migrations/2026_02_20_212853_add_team_id_to_personal_access_tokens_table.php` (added `->index()`)
- `app/Models/PersonalAccessToken.php` (added `booted()` with `updating` event)
- `tests/Feature/PersonalAccessToken/TeamIdImmutabilityTest.php` (new)
- `.gitignore` (added `.context`)

### Learnings for future iterations:
- `laravel/mcp` was only available transitively through `laravel/boost` (require-dev) -- production `composer install --no-dev` would miss it entirely
- PostgreSQL does NOT auto-create indexes on foreign key referencing columns (unlike MySQL) -- always add `->index()` explicitly
- Rector has `ThrowIfRector` that converts `if (condition) { throw ... }` to `throw_if()` -- let rector handle this pattern
- `PersonalAccessToken` creation flow uses `createToken()` then `fill(['team_id' => ...])->save()` -- immutability must allow null->value transition
- Pre-existing test failures (11 total): `ResolvesEntitySchema.php` toCollection() on null (7), `CustomFieldsApiTest` (1), `InstallCommandTest` (3) -- all unrelated to this work

## US-003: Harden mass assignment and data exposure
- Added `Arr::only()` field whitelisting to all 10 Create/Update actions (Company, People, Opportunity, Task, Note) as defense-in-depth against mass assignment
- Updated `/api/user` endpoint to return `UserResource` (JSON:API format) instead of raw model -- exposes only `name` and `email`
- Changed `CreationSource::API` badge color from `'info'` to `'purple'` to distinguish it from `CreationSource::WEB`
- 3 new tests verifying `/api/user` response shape, attribute whitelist, and excluded sensitive fields

### Files changed
- `app/Actions/Company/CreateCompany.php` (added `Arr::only()`)
- `app/Actions/Company/UpdateCompany.php` (added `Arr::only()`)
- `app/Actions/People/CreatePeople.php` (added `Arr::only()`)
- `app/Actions/People/UpdatePeople.php` (added `Arr::only()`)
- `app/Actions/Opportunity/CreateOpportunity.php` (added `Arr::only()`)
- `app/Actions/Opportunity/UpdateOpportunity.php` (added `Arr::only()`)
- `app/Actions/Task/CreateTask.php` (added `Arr::only()`)
- `app/Actions/Task/UpdateTask.php` (added `Arr::only()`)
- `app/Actions/Note/CreateNote.php` (added `Arr::only()`)
- `app/Actions/Note/UpdateNote.php` (added `Arr::only()`)
- `routes/api.php` (wrapped `/api/user` in `UserResource`)
- `app/Enums/CreationSource.php` (changed API color to `'purple'`)
- `tests/Feature/Api/V1/UserEndpointTest.php` (new)

### Learnings for future iterations:
- `UsesCustomFields` trait dynamically merges `custom_fields` into `$fillable` -- models like Note/Opportunity may only list `creation_source` explicitly in `$fillable` but accept other fields via the trait's `saving` event interception
- `JsonApiResource` response wraps in `{ "data": { "id", "type", "attributes" } }` format -- tests should assert on `data.attributes` path
- `Arr::only()` in actions is defense-in-depth: Form Requests validate input, model `$fillable` guards mass assignment, and now actions whitelist too -- prevents future drift if someone adds a column to `$fillable` without thinking about API exposure
- Pre-existing PHPStan errors (5 total): all `ResolvesEntitySchema.php` `toCollection()` on null -- unchanged from previous stories

## US-004: Decouple List actions from global request and fix Octane safety
- Refactored all 5 List actions (ListCompanies, ListPeople, ListOpportunities, ListTasks, ListNotes) to accept explicit `$perPage`, `$useCursor`, `$filters`, `$page`, and `$request` parameters instead of reading from the global `request()` helper
- Updated all 5 MCP List tools to pass parameters directly to actions -- eliminated all `request()->merge()` calls
- Updated all 5 API controllers to pass the HTTP request and extracted pagination params explicitly
- Spatie QueryBuilder now receives an explicit `$request` parameter via `QueryBuilder::for($query, $request)` instead of reading from the global request singleton
- Fixed `SetApiTeamContext::terminate()` to call `auth()->guard('web')->forgetUser()` before clearing tenant context -- prevents Octane state leakage of the authenticated user across requests
- Fixed N+1 query on `$token->team` in `SetApiTeamContext::resolveTeam()` -- replaced lazy-loaded relationship with direct `Team::query()->find($token->team_id)`

### Files changed
- `app/Actions/Company/ListCompanies.php` (new signature with explicit params)
- `app/Actions/People/ListPeople.php` (new signature with explicit params)
- `app/Actions/Opportunity/ListOpportunities.php` (new signature with explicit params)
- `app/Actions/Task/ListTasks.php` (new signature with explicit params)
- `app/Actions/Note/ListNotes.php` (new signature with explicit params)
- `app/Mcp/Tools/Company/ListCompaniesTool.php` (removed `request()->merge()`)
- `app/Mcp/Tools/People/ListPeopleTool.php` (removed `request()->merge()`)
- `app/Mcp/Tools/Opportunity/ListOpportunitiesTool.php` (removed `request()->merge()`)
- `app/Mcp/Tools/Task/ListTasksTool.php` (removed `request()->merge()`)
- `app/Mcp/Tools/Note/ListNotesTool.php` (removed `request()->merge()`)
- `app/Http/Controllers/Api/V1/CompaniesController.php` (pass explicit params + request)
- `app/Http/Controllers/Api/V1/PeopleController.php` (pass explicit params + request)
- `app/Http/Controllers/Api/V1/OpportunitiesController.php` (pass explicit params + request)
- `app/Http/Controllers/Api/V1/TasksController.php` (pass explicit params + request)
- `app/Http/Controllers/Api/V1/NotesController.php` (pass explicit params + request)
- `app/Http/Middleware/SetApiTeamContext.php` (terminate: forgetUser; resolveTeam: direct query)

### Learnings for future iterations:
- Spatie QueryBuilder's `::for()` method accepts an optional `?Request $request` parameter -- pass it explicitly to avoid coupling to the global request singleton
- `new Request(['filter' => $filters])` creates a synthetic Illuminate Request with query parameters that Spatie QueryBuilder can read -- useful for MCP/CLI contexts where there's no HTTP request
- `$query->paginate($perPage, ['*'], 'page', $page)` -- the 4th positional arg overrides the page number, bypassing the paginator's default of reading from the request
- `auth()->guard('web')->forgetUser()` is the correct way to clear the web guard user set by `setUser()` -- without this, Octane would leak the authenticated user to the next request
- `$token->team` triggers a lazy-loaded relationship query each time -- use `Team::query()->find($token->team_id)` to make the query explicit and avoid potential N+1
- Pre-existing test failures (12 total): ResolvesEntitySchema (7), CustomFieldsApiTest (1), InstallCommandTest (3), ApiTeamScopingTest `/api/user` (1, from US-003 UserResource change) -- all unrelated to this work

## US-005: Add pagination and caching to unbounded endpoints
- Added pagination to `CustomFieldsController::index()` -- uses `paginate()` instead of `get()`, with `per_page` query param (default 15, max 100) and validation
- Added 60-second caching to `ResolvesEntitySchema::resolveCustomFields()` -- cache key: `custom_fields_schema_{teamId}_{entityType}`
- Added 60-second caching to `CrmOverviewPrompt::handle()` -- cache key: `crm_overview_{teamId}`, wraps all 7 queries in a single `Cache::remember()` call
- Updated 6 existing tests to use `per_page=100` since seeded data exceeds the default page size
- Added 5 new pagination tests: default pagination, per_page parameter, max cap validation, non-integer rejection, page navigation

### Files changed
- `app/Http/Controllers/Api/V1/CustomFieldsController.php` (added `paginate()`, `per_page` validation, `MAX_PER_PAGE` constant)
- `app/Mcp/Resources/Concerns/ResolvesEntitySchema.php` (added `Cache::remember()` with 60s TTL)
- `app/Mcp/Prompts/CrmOverviewPrompt.php` (wrapped queries in `Cache::remember()` with 60s TTL, added user/team resolution)
- `tests/Feature/Api/V1/CustomFieldsApiTest.php` (5 new pagination tests, updated 6 existing tests for pagination compatibility)

### Learnings for future iterations:
- When switching from `get()` to `paginate()`, existing tests that search for specific records need `per_page=100` (or similar) to ensure all results are visible -- seeded data from factories/seeders can exceed the default page size
- `Cache::remember($key, 60, fn() => ...)` uses seconds (not minutes) as TTL -- pass an integer for seconds or use `now()->addMinutes(1)` for clarity
- MCP prompts access the user via `$request->user()` (same as resources), while MCP tools use `auth()->user()` -- different patterns for the same auth context
- Rector's `SimplifyUselessVariableRector` will inline the last assignment + return into a single `return` statement -- let rector handle it rather than fighting it
- Pre-existing test failures unchanged (12 total): ResolvesEntitySchema (7), CustomFieldsApiTest (1), InstallCommandTest (3), ApiTeamScopingTest (1)

## US-006: Fill critical test coverage gaps
- Added 19 new tests in `CriticalCoverageTest.php` covering 6 critical untested paths:
  - **Expired token rejection** (1 test): token with `expires_at` in the past returns 401
  - **Token-based team scoping** (2 tests): token with `team_id` resolves correct team context using real tokens (not `Sanctum::actingAs`); token `team_id` takes priority over `X-Team-Id` header
  - **Soft-delete visibility** (8 tests): soft-deleted records excluded from list and show endpoints for People, Notes, Tasks, Opportunities (Companies already had coverage)
  - **ForceJsonResponse middleware** (2 tests): JSON response returned without `Accept` header for both successful requests and validation errors
  - **Rate limiting** (1 test): returns 429 after exceeding threshold (uses `RateLimiter::for()` override for fast testing)
  - **Non-existent UUID** (5 tests): returns 404 for non-existent ULID across all 5 entity types

### Files changed
- `tests/Feature/Api/V1/CriticalCoverageTest.php` (new -- 19 tests, 30 assertions)

### Learnings for future iterations:
- `createToken()` doesn't accept `team_id` -- create tokens via `$user->tokens()->create([...])` with a raw hash and compose `"{$token->id}|{$raw}"` for the plain text token
- `ForceJsonResponse` middleware runs AFTER `auth:sanctum` but BEFORE `SubstituteBindings` -- unauthenticated requests without `Accept` header get redirected (302) not 401, because auth middleware runs first
- However, `SubstituteBindings` 404s still render as HTML because the exception handler checks `$request->expectsJson()` before `ForceJsonResponse` has run -- route model binding failures bypass the middleware
- `RateLimiter::for('api', fn () => Limit::perMinute(N))` can be overridden in tests to use a low threshold for fast rate limit testing
- Soft-delete tests were only present for Companies in the API -- People, Notes, Tasks, Opportunities had no API soft-delete coverage despite all using `SoftDeletes` trait
- Pre-existing PHPStan errors unchanged (5 total): all `ResolvesEntitySchema.php` `toCollection()` on null
