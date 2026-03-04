# Relaticle CRM

## Cursor Cloud specific instructions

### Overview
Relaticle is a multi-tenant CRM built with PHP 8.4, Laravel 12, Filament 5, and Livewire 4. PostgreSQL is the sole database backend (no SQLite/MySQL).

### Services
- **PostgreSQL** (port 5432): Start with `sudo pg_ctlcluster 16 main start`. Databases: `relaticle` (dev), `relaticle_testing` (tests). User: `postgres` / `postgres`.
- **Redis** (port 6379): Start with `sudo redis-server --daemonize yes`. Used for cache in dev.
- **Laravel dev server**: `php artisan serve --host=0.0.0.0 --port=8000`
- **Vite dev server**: `npm run dev` (port 5173, provides HMR)
- **All-in-one dev**: `composer dev` starts server, queue worker, log tail, and Vite concurrently.

### Running tests
- Tests require the `relaticle_testing` database to exist and `npm run build` for Vite manifest.
- `composer test:pest` runs the Pest test suite in parallel; `composer test` runs lint + types + tests.
- See `composer.json` scripts section for all available test/lint commands.

### Gotchas
- The committed `.env.testing` has `DB_USERNAME=root` and empty password, which does not match the standard PostgreSQL setup. It must be updated to `DB_USERNAME=postgres` / `DB_PASSWORD=postgres` for tests to connect.
- ImportWizard tests require `php8.4-sqlite3` (pdo_sqlite driver) because imports use per-import SQLite databases for staging data.
- Run `npm run build` at least once before running the test suite, or tests that render Filament views will fail with "Vite manifest not found."
- The `composer dev` script uses `npx concurrently` — it requires npm dependencies to be installed.
