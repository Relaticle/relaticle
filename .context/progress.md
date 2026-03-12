## Codebase Patterns

- **Testing with real tokens**: Use `$user->createToken('name', ['ability'])->plainTextToken` with `$this->withToken($token)` for API tests that need ability enforcement. `Sanctum::actingAs()` in v4 creates Mockery mocks that don't work with `instanceof PersonalAccessToken` checks.
- **MCP testing with tokens**: Use `$user->createToken('name', ['ability'])` then `$user->withAccessToken($token->accessToken)` before `RelaticleServer::actingAs($user)` to set a real token on the user for MCP tool tests.
- **Token detection pattern**: Check `$token instanceof PersonalAccessToken && $token->getKey()` to only enforce abilities on real persisted tokens. This skips TransientToken (session auth), Mockery mocks (tests), and null tokens.

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
