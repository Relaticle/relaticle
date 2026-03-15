# Project

This is production code for a commercial SaaS product with paying customers.
Bugs directly impact revenue and user trust.

Treat every change like it's going through senior code review:

- No lazy shortcuts or placeholder code
- Handle errors and edge cases properly
- Write code that won't embarrass you in 6 months

## Database

- This project uses **PostgreSQL exclusively** — do not add SQLite/MySQL compatibility layers, driver checks, or conditional SQL
- Migrations must only have `up()` methods — do not write `down()` methods

## Pre-Commit Quality Checks

Before committing any changes, always run these checks in order:

1. `vendor/bin/pint --dirty --format agent` — fix code style
2. `vendor/bin/rector --dry-run` — if rector suggests changes, apply them with `vendor/bin/rector`
3. `vendor/bin/phpstan analyse` — ensure no new static analysis errors
4. `composer test:type-coverage` — ensure type coverage stays at or above 99.9%
5. `php artisan test --compact` — run relevant tests (use `--filter` for targeted runs)

Do not add new PHPStan errors to the baseline without approval. All parameters and return types must be explicitly typed — untyped closures/parameters will fail type coverage in CI.

## Icons (Remix Icon)

- **Brand/social icons** (GitHub, Discord, Twitter, LinkedIn) → always `fill` variant
- **UI/functional icons** (arrows, chevrons, checks, close) → always `line` variant
- **Feature/section icons** → `line` variant, stay consistent within a section
- **Status/emphasis icons** (success checkmarks, alerts) → `fill` variant

## Scheduling

- All scheduled commands go in `bootstrap/app.php` via `withSchedule()` — not in `routes/console.php`

## Testing

- Focus on real-world integration tests over isolated unit tests
- Use `mutates(ClassName::class)` in test files to declare which source classes each test covers
- Run mutation testing per-class: `php -d xdebug.mode=coverage vendor/bin/pest --mutate --class='App\MyClass' tests/path/`
- No enforced `--min` threshold — use mutation testing as a code review tool, not a CI gate

## Custom Fields

- Models using the `UsesCustomFields` trait handle `custom_fields` automatically — do NOT manually extract, strip, or call `saveCustomFields()` in actions
- The trait merges `'custom_fields'` into `$fillable`, intercepts it during `saving`, and persists values during `saved` — just pass `custom_fields` through in the `$data` array to `create()`/`update()`
- Tenant context for the custom-fields package is set in `SetApiTeamContext` middleware via `TenantContextService::setTenantId()` — actions don't need `withTenant()` wrappers
- In Filament, the package's own `SetTenantContextMiddleware` handles tenant context — no action-level code needed there either
- `CustomFieldValidationService` intentionally uses explicit `where('tenant_id', ...)` with `withoutGlobalScopes()` — this is defensive and correct, don't change it to rely on ambient state
