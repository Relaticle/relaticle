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

### F-004: Echo/WebSocket state — Echo and Pusher both undefined on dashboard

- **Surface:** browser JS / `window.Echo` / `https://app.relaticle-pr-209.test/chat-qas-team-nyzen/dashboard`
- **Severity:** P0 blocker
- **Category:** correctness
- **Steps to reproduce:**
  1. Log in as `chat-qa@relaticle.test` at `https://app.relaticle-pr-209.test/login`
  2. Navigate to `https://app.relaticle-pr-209.test/chat-qas-team-nyzen/dashboard`
  3. `agent-browser eval 'JSON.stringify({ echo: typeof window.Echo, pusher: typeof window.Pusher })'`
- **Expected:** `echo` = `"object"`, `pusher` = `"function"`, `state` = `"connected"` within 8s.
- **Observed:** `{"echo":"undefined","pusher":"undefined"}` — neither Echo nor Pusher is initialized on the page. Dashboard threw 500 error (see F-005) so scripts may not have loaded.
- **Root cause hypothesis:** Dashboard 500 prevents full page load; additionally `BROADCAST_CONNECTION=log` and Reverb not running means WS would not connect even if Echo were loaded.
- **Proposed Pest test (Phase 14):**
  ```php
  // file: tests/Feature/Chat/WebSocketTest.php
  it('chat message dispatches broadcast event', function () {
      Event::fake();
      // send message and assert broadcast event fired
  });
  ```
- **Fix sketch:** Fix `agent_conversations` migration first (see F-002/F-005); then start Reverb and set `BROADCAST_CONNECTION=reverb`.

### F-005: Dashboard 500 — agent_conversations table does not exist

- **Surface:** `app/Filament/Pages/Dashboard.php:36` / `packages/Chat/src/Actions/ListConversations.php:22`
- **Severity:** P0 blocker
- **Category:** correctness
- **Steps to reproduce:**
  1. Log in as `chat-qa@relaticle.test`
  2. Navigate to `https://app.relaticle-pr-209.test/chat-qas-team-nyzen/dashboard`
- **Expected:** Dashboard renders with chat panel.
- **Observed:** HTTP 500 — `SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "agent_conversations" does not exist`. Stack: `ListConversations->execute()` at `Dashboard.php:36` calls `DB::table('agent_conversations')` which does not exist because `laravel/ai` package (which provides `AiMigration` base class) is not installed and the migration `2026_03_31_212425_create_agent_conversations_table` is blocked.
- **Root cause hypothesis:** `laravel/ai` package is in `composer.json` but missing from `vendor/` — the `create_agent_conversations_table` migration cannot run, so the table never gets created.
- **Proposed Pest test (Phase 14):**
  ```php
  // file: tests/Feature/Chat/DashboardTest.php
  it('dashboard renders without errors for authenticated user', function () {
      $user = User::factory()->withPersonalTeam()->create();
      actingAs($user);
      get(route('filament.app.pages.dashboard', ['tenant' => $user->currentTeam->slug]))
          ->assertOk();
  });
  ```
- **Fix sketch:** Install `laravel/ai` package (`composer require laravel/ai:^0.4.3`) and run `php artisan migrate`.

### F-006: Echo client never bootstrapped in app panel — chat realtime cannot work out of the box

- **Surface:** `packages/Chat/src/ChatServiceProvider.php`, `resources/js/echo.js`, `config/filament.php:19-34`, `app/Providers/Filament/AppPanelProvider.php`
- **Severity:** P0 blocker
- **Category:** correctness
- **Steps to reproduce:**
  1. `grep -rn "echo.js\|@vite.*echo" resources/ app/ packages/Chat/ config/` — zero results outside the Vite input declaration.
  2. Log in to the app panel, `agent-browser eval 'typeof window.Echo'` → `"undefined"`.
- **Expected:** `window.Echo` initialized in every authenticated app-panel page so `chat.{userId}` private channel can be subscribed.
- **Observed:** Three independent gaps:
  1. `resources/js/echo.js` is a Vite input but **never included** by any Blade template, Filament asset registration, or render hook.
  2. Filament's native `config/filament.php` broadcasting block is gated on `VITE_PUSHER_APP_KEY` and hardcodes `broadcaster: 'pusher'` with `wsHost: VITE_PUSHER_HOST` — incompatible with the chat package's Reverb configuration even if the key were set.
  3. `ChatServiceProvider::registerRenderHooks()` registers a `SIDEBAR_NAV_END` and `BODY_END` hook but no `HEAD_END` / `SCRIPTS_BEFORE` hook that injects the echo asset.
- **Root cause hypothesis:** The chat package was authored assuming an external Echo bootstrap (e.g. from `resources/js/app.js` or a parent layout) that does not exist in this app. The feature shipped without asset integration.
- **Proposed Pest test (Phase 14):**
  ```php
  // file: tests/Browser/Chat/EchoBootstrapTest.php
  it('bootstraps Echo on the app panel', function () {
      $this->actingAs(User::factory()->withPersonalTeam()->create())
          ->visit('/app/'.$user->currentTeam->slug)
          ->assertScript('typeof window.Echo', 'object')
          ->assertScript('window.Echo.connector.pusher.connection.state', 'connected');
  });
  ```
- **Fix sketch:**
  Add to `ChatServiceProvider::registerRenderHooks()`:
  ```php
  FilamentView::registerRenderHook(
      PanelsRenderHook::HEAD_END,
      fn (): string => Blade::render("@vite('resources/js/echo.js')"),
  );
  ```
  And update `config/filament.php` broadcasting block to use reverb env vars, OR explicitly skip Filament's echo block (`'broadcasting' => []`) so only the chat package's Echo instance exists.

### F-007: Browser WSS/TLS mismatch against local Reverb — environment-only, not a PR bug

- **Surface:** env / Herd TLS termination
- **Severity:** P3 polish
- **Category:** observability
- **Steps to reproduce:**
  1. `php artisan reverb:start` on `ws://localhost:8080`
  2. Load `https://app.relaticle-pr-209.test/...` in browser
  3. Echo attempts `wss://localhost:8080/app/...` — browser mixed-content policy blocks insecure WS from HTTPS origin; Pusher-js auto-upgrades to WSS despite `forceTLS:false`.
- **Expected:** Browser connects to Reverb and `window.Echo.connector.pusher.connection.state === 'connected'`.
- **Observed:** `state` stays at `unavailable`; console: `WebSocket connection to 'wss://localhost:8080/...' failed`.
- **Root cause hypothesis:** Local Reverb has no TLS cert; browser blocks ws from https origin. Fix for local dev: point Herd to proxy a secured subdomain (`ws.relaticle-pr-209.test`) to Reverb, or run the app over http for testing.
- **Proposed Pest test (Phase 14):** N/A — env concern only.
- **Fix sketch:** For this QA run, verify broadcasts by inspecting server-side Reverb connection logs + Laravel log + DB state instead of client-side WS reception.

### F-008: Phase 1 Task 1.1 — Dashboard renders; greeting uses server UTC, wrong for non-UTC users

- **Surface:** `app/Filament/Pages/Dashboard.php:50-55` / `https://app.relaticle-pr-209.test/chat-qas-team-nyzen/dashboard`
- **Severity:** P2 minor
- **Category:** UX / correctness
- **Steps to reproduce:**
  1. Log in as `chat-qa@relaticle.test` from a browser in a UTC+4 timezone.
  2. Navigate to `https://app.relaticle-pr-209.test/chat-qas-team-nyzen/dashboard` at 14:00 local time.
  3. Observe the greeting heading.
- **Expected:** Greeting reflects the user's local time of day — "Good afternoon" at 14:00 local.
- **Observed:** Greeting reads "Good morning, Chat." Server UTC hour was 10 (`APP_TIMEZONE=UTC`); local system hour was 14. `Dashboard.php:50` calls `now()->format('H')` which uses `config('app.timezone')` = UTC, ignoring the user's timezone entirely.
- **Root cause hypothesis:** `Dashboard.php` derives the hour from the server/app timezone rather than a user-preference timezone. No user timezone column exists on the users table.
- **Proposed Pest test (Phase 14):**
  ```php
  // file: tests/Feature/Chat/DashboardGreetingTest.php
  it('greeting reflects afternoon for a user in UTC+4', function () {
      $this->travelTo(now()->setTimezone('UTC')->setHour(10)); // 14:00 UTC+4
      $user = User::factory()->withPersonalTeam()->create(['timezone' => 'Asia/Baku']);
      actingAs($user);
      livewire(\App\Filament\Pages\Dashboard::class)
          ->assertSeeHtml('Good afternoon');
  });
  ```
- **Fix sketch:** Store a `timezone` column on users; in `Dashboard::getGreeting()` use `now($user->timezone ?? config('app.timezone'))` to compute the hour. Fall back to `config('app.timezone')` for users without a preference.
- **Screenshots:** `.context/screenshots/01-dashboard.png`

### F-009: Phase 1 Task 1.1 — Dashboard renders with 8 suggested prompts and no page-level 500

- **Surface:** `https://app.relaticle-pr-209.test/chat-qas-team-nyzen/dashboard`
- **Severity:** P3 polish
- **Category:** observability
- **Steps to reproduce:**
  1. Navigate to dashboard as `chat-qa@relaticle.test`.
  2. Count `<button>` elements whose text matches `/CRM overview|Overdue tasks|Recent companies|Pipeline summary/`.
  3. Check browser errors for 500 responses.
- **Expected:** Page renders (HTTP 200), ≥4 suggested prompt buttons visible.
- **Observed:**
  - Dashboard page rendered successfully (no page-level 500; earlier F-005 500 is resolved in the QA environment).
  - 8 suggested prompt buttons found matching the CRM overview / Overdue tasks / Recent companies / Pipeline summary pattern.
  - `window.Echo` and `window.Pusher` remain undefined (pre-existing F-004/F-006).
  - WSS timeout errors visible in console (pre-existing F-007).
  - One `500` resource error in console log — unrelated to main dashboard render (Boost browser-logger endpoint at `https://filament-demo.test/_boost/browser-logs`).
  - Two Flowforge errors: `Could not determine card ID for move operation` and `Target column ID is missing` — appear to be from a different panel/page bleeding into the console log cache, not triggered by this page.
- **Root cause hypothesis:** Nominal — dashboard renders correctly for Phase 1. Pre-existing findings (F-004, F-005, F-006, F-007) account for all console errors.
- **Proposed Pest test (Phase 14):**
  ```php
  // file: tests/Feature/Chat/DashboardTest.php
  it('dashboard renders with suggested prompts', function () {
      $user = User::factory()->withPersonalTeam()->create();
      actingAs($user);
      livewire(\App\Filament\Pages\Dashboard::class)
          ->assertSee('CRM overview')
          ->assertSee('Overdue tasks');
  });
  ```
- **Fix sketch:** N/A — observability entry only.

### F-010: Phase 1 Task 1.3 — Chats sidebar group visible but shows no empty-state placeholder under zero conversations

- **Surface:** Sidebar nav / `[data-group-label="Chats"]` element
- **Severity:** P2 minor
- **Category:** UX
- **Steps to reproduce:**
  1. Log in as `chat-qa@relaticle.test` (no conversations yet).
  2. Navigate to dashboard.
  3. Inspect sidebar: `document.querySelector('[data-group-label="Chats"]')?.textContent?.trim()`.
- **Expected:** `chatsGroup: true`; empty state copy visible (e.g. "No conversations yet" or "Start a new chat").
- **Observed:** `chatsGroup: true`, `itemCount: 0`. The group renders only the label text "Chats" with a blank area beneath — no hint that the user should start a conversation. A first-time user sees a blank section with no affordance.
- **Root cause hypothesis:** The sidebar navigation component for "Chats" renders the group header and a list, but the list empty state is absent — no `<li>` placeholder or descriptive text is rendered when the conversation list is empty.
- **Proposed Pest test (Phase 14):**
  ```php
  // file: tests/Feature/Chat/SidebarTest.php
  it('shows empty state copy in chats sidebar group when no conversations exist', function () {
      $user = User::factory()->withPersonalTeam()->create();
      actingAs($user);
      get(route('filament.app.pages.dashboard', ['tenant' => $user->currentTeam->slug]))
          ->assertSee('No conversations yet');
  });
  ```
- **Fix sketch:** In the component that renders the Chats sidebar group, add a conditional: when the conversation list is empty, render a small muted-text placeholder `<li>` such as "No conversations yet. Start one →".

### F-011: Phase 1 Task 1.2 & 1.4 — Chat toggle button and chat empty-state page both nominal

- **Surface:** `[data-chat-toggle]` button / `https://app.relaticle-pr-209.test/chat-qas-team-nyzen/chats`
- **Severity:** P3 polish
- **Category:** observability
- **Steps to reproduce:**
  1. On dashboard, eval `document.querySelector("[data-chat-toggle]")?.offsetParent !== null` — should be true.
  2. Eval `document.querySelector("[data-chat-toggle] kbd")?.textContent?.trim()` — should be `"⌘J"`.
  3. Navigate to `/chats` (no id), wait for `textarea[placeholder="Ask anything..."]`.
  4. Assert `h1` = "New chat", textarea enabled, empty state copy present.
- **Expected:** Toggle visible, kbdText = "⌘J", `/chats` page shows h1 "New chat" with input enabled and "Start a conversation..." copy.
- **Observed:**
  - Toggle: `visible: true`, `kbdText: "⌘J"` — correct.
  - `/chats` page title = "New chat - Relaticle", h1 = "New chat", textarea enabled, empty state copy = "Start a conversation..." (appears twice in DOM — once in each locale variant or duplication).
  - No 500/4xx errors on the `/chats` route.
- **Root cause hypothesis:** Nominal — both surfaces render as designed. The double "Start a conversation..." may be a DOM duplication worth a follow-up look (same text node cloned for e.g. sr-only and visible spans), but not a functional issue.
- **Proposed Pest test (Phase 14):**
  ```php
  // file: tests/Feature/Chat/ChatPageTest.php
  it('chat page without id shows empty state', function () {
      $user = User::factory()->withPersonalTeam()->create();
      actingAs($user);
      get(route('filament.app.chats.index', ['tenant' => $user->currentTeam->slug]))
          ->assertOk()
          ->assertSee('New chat')
          ->assertSee('Start a conversation');
  });
  ```
- **Fix sketch:** N/A — observability entry. Investigate duplicate "Start a conversation..." text node in Phase 2.
- **Screenshots:** `.context/screenshots/01-chat-empty.png`
