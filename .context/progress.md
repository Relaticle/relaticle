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
