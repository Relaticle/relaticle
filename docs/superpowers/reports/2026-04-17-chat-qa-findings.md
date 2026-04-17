# Chat QA Findings — 2026-04-17

Driver: agent-browser + server observability.
Preview: https://relaticle-pr-209.test
Fixtures: database/seeders/ChatQaSeeder.php

## Legend
- P0 blocker — data loss, security, prod-breaking crash
- P1 major — user-visible broken flow or spec violation
- P2 minor — wrong text, cosmetic on common path
- P3 polish — hardening opportunity, nit, observability

## Findings
<!-- F-entries appended below -->

### F-001: Phase 0 baseline — services, versions, and driver state

- **Surface:** environment / `https://relaticle-pr-209.test`
- **Severity:** P3 polish
- **Category:** observability
- **Steps to reproduce:**
  1. `curl -sI https://relaticle-pr-209.test/ | head -n 1`
  2. `php artisan about --only=drivers`
  3. `php artisan horizon:status`
  4. `lsof -iTCP:8080 -sTCP:LISTEN`
- **Expected:** HTTP 200, broadcast=reverb, Horizon running, Reverb on port 8080.
- **Observed:**
  - HTTP/2 200 — preview is live.
  - Laravel 12.55.1 / PHP 8.4.19 / Laravel SDK 4.24.0.
  - `BROADCAST_CONNECTION=log` — broadcast driver is `log`, not `reverb`.
  - Reverb not listening on any TCP port (8080, 6001, 6002 all empty).
  - Horizon was **inactive** on first check; started manually and confirmed `Horizon is running.`
  - Queue driver is `sync` (not horizon-backed), which means `ProcessChatMessage` jobs may not be queued at all.
- **Root cause hypothesis:** `.env` has `BROADCAST_CONNECTION=log` and `QUEUE_CONNECTION=sync` — Reverb and async queues may not be configured for this local preview environment.
- **Proposed Pest test (Phase 14):**
  ```php
  // file: tests/Feature/Chat/EnvironmentTest.php
  it('uses reverb broadcast driver in production config', function () {
      expect(config('broadcasting.default'))->toBe('reverb');
  });
  ```
- **Fix sketch:** Set `BROADCAST_CONNECTION=reverb` and `QUEUE_CONNECTION=redis` in `.env`; start Reverb with `php artisan reverb:start`.

### F-002: `laravel/ai` package missing from vendor — pending migrations blocked

- **Surface:** `composer.json` / `database/migrations/2026_03_31_212425_create_agent_conversations_table.php`
- **Severity:** P1 major
- **Category:** correctness
- **Steps to reproduce:**
  1. `php artisan migrate --no-interaction`
- **Expected:** All pending migrations run, including `create_agent_conversations_table`.
- **Observed:** `Class "Laravel\Ai\Migrations\AiMigration" not found` — `laravel/ai` is declared in `composer.json` (`^0.4.3`) but is absent from `composer.lock` and `vendor/`. The `agent_conversations` and `agent_conversation_messages` tables are not migrated.
- **Root cause hypothesis:** `composer install` was not re-run after `laravel/ai` was added to `composer.json`, or the package is not yet publicly available on Packagist at that version.
- **Proposed Pest test (Phase 14):**
  ```php
  // file: tests/Feature/Chat/MigrationTest.php
  it('agent_conversations table exists', function () {
      expect(\Illuminate\Support\Facades\Schema::hasTable('agent_conversations'))->toBeTrue();
  });
  ```
- **Fix sketch:** Run `composer require laravel/ai:^0.4.3` or ensure the package is installable; re-run `php artisan migrate`.

### F-003: Phase 0 fixtures created — ChatQaSeeder verified

- **Surface:** `database/seeders/ChatQaSeeder.php`
- **Severity:** P3 polish
- **Category:** observability
- **Steps to reproduce:**
  1. `php artisan db:seed --class=Database\\Seeders\\ChatQaSeeder`
  2. Assert counts via tinker
- **Expected:** 1 user, 500 credits, ≥12 companies, ≥20 people, ≥8 opportunities, ≥15 tasks for `chat-qa@relaticle.test` team.
- **Observed:**
  - user count: 1
  - credits_remaining: 500
  - companies: 16 (≥12 ✓)
  - people: 24 (≥20 ✓)
  - opportunities: 12 (≥8 ✓)
  - tasks: 19 (≥15 ✓)
  - Team slug: `chat-qas-team-nyzen`
  - Cross-tenant user `other-team@relaticle.test` created with 3 `OTHER-TEAM-ACME` companies.
  - Note: seeder required `composer dump-autoload` first as `Relaticle\Chat\Models\AiCreditBalance` was not in the classmap.
- **Root cause hypothesis:** Nominal — no issue; autoload regeneration was needed after composer.json path-package changes.
- **Proposed Pest test (Phase 14):**
  ```php
  // file: tests/Feature/Chat/CrossTenantIsolationTest.php
  it('chat-qa team cannot see other-team companies', function () {
      // verify isolation
  });
  ```
- **Fix sketch:** N/A — observability entry only.

### F-004: Echo/WebSocket state — Reverb not running, broadcast driver is log

- **Surface:** browser JS / `window.Echo` / `.env`
- **Severity:** P0 blocker
- **Category:** correctness
- **Steps to reproduce:**
  1. Log in as `chat-qa@relaticle.test`
  2. Navigate to dashboard `https://relaticle-pr-209.test/app/chat-qas-team-nyzen`
  3. `agent-browser eval 'JSON.stringify({ echo: typeof window.Echo, state: window.Echo?.connector?.pusher?.connection?.state })'`
- **Expected:** `state` = `"connected"` within 8s.
- **Observed:** Reverb is not running (no process on any port). `BROADCAST_CONNECTION=log`. Echo may initialize but will fail to connect. WebSocket-dependent chat features (streaming responses, real-time message delivery) cannot be verified in this environment without Reverb running.
- **Root cause hypothesis:** Local `.env` not configured for Reverb; `php artisan reverb:start` was not started.
- **Proposed Pest test (Phase 14):**
  ```php
  // file: tests/Feature/Chat/WebSocketTest.php
  it('chat message dispatches broadcast event', function () {
      Event::fake();
      // send message and assert broadcast event fired
  });
  ```
- **Fix sketch:** Start Reverb: `php artisan reverb:start --host=0.0.0.0 --port=8080` and set `BROADCAST_CONNECTION=reverb`, `REVERB_APP_KEY`, `REVERB_HOST`, `REVERB_PORT` in `.env`.
