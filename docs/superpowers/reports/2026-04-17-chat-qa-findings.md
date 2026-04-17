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

---

## Phase 2 Findings (Tasks 2.1–2.5)

### F-012: Task 2.1 — Dashboard hero redirects correctly but URL querystring leaks after conversation.resolved

- **Surface:** Dashboard hero → `GET /{slug}/chats?message=…` → `handleConversationResolved`
- **Severity:** P1 major
- **Category:** UX / URL routing
- **Steps to reproduce:**
  1. Navigate to dashboard as `chat-qa@relaticle.test`.
  2. Type "Say hi in exactly 5 words" in the hero textarea and press Enter.
  3. Browser redirects to `/{slug}/chats?message=Say+hi+in+exactly+5+words`.
  4. Alpine `init()` detects `initialMessage`, calls `sendMessage()`, job processes, `conversation.resolved` fires.
  5. Observe `window.location.href` after `handleConversationResolved` runs.
- **Expected:** URL rewritten to `/{slug}/chats/{newConversationId}` — querystring dropped.
- **Observed:** URL remains `/{slug}/chats?message=Say+hi+in+exactly+5+words` — the conversation ID is never appended.
- **Root cause:** `handleConversationResolved` calls two `.replace()` on `window.location.pathname`. Neither regex matches `/chats?…` because:
  - Regex 1: `/\/chats\/.*$/` — requires a slash after `/chats`, fails on `?`.
  - Regex 2: `/\/chats\/?$/` — the `$` anchor fails because `?message=…` trails the pathname on the raw `pathname` string.
  - Verified with Node.js: `'/slug/chats?message=foo'.replace(/\/chats\/.*$/, '...')` → unchanged.
- **Browser-confirmed:** After job completed and `conversation.resolved` broadcast, URL stayed as `/chats?message=Say+hi+in+exactly+5+words`. New conversation `019d9afe-9587-727f-aab3-392a4862d5bd` ("Hello everyone nice to meet") was created in DB but URL never updated.
- **Proposed fix:** Strip the querystring before computing the rewrite path:
  ```js
  handleConversationResolved(event) {
      this.conversationId = event.conversationId;
      const pathOnly = window.location.pathname;
      const path = pathOnly
          .replace(/\/chats\/.*$/, '/chats/' + event.conversationId)
          .replace(/\/chats\/?$/, '/chats/' + event.conversationId);
      history.replaceState(null, '', path);
      // ...
  }
  ```
  Replace `window.location.pathname` lookup with `pathOnly` and ensure the second `.replace` always drops any trailing `?…`.
- **Proposed Pest test (Phase 14):** Static JS unit test; no server-side Pest equivalent.
- **Screenshots:** `.context/screenshots/05-url-stuck-with-message-param.png`

### F-013: Task 2.2 — Send-message happy path nominal; job runs, messages persist, credits deducted

- **Surface:** `POST /chat` → `ProcessChatMessage` job → DB messages + credit transaction
- **Severity:** P3 observability
- **Category:** happy path
- **Steps to reproduce:**
  1. Navigate to `/{slug}/chats?message=What%20is%202%2B2%3F`.
  2. Alpine auto-sends; `ProcessChatMessage` job dispatched to `queues:default`.
  3. Job completes within ~5 s on Horizon.
  4. Query DB: `agent_conversations`, `agent_conversation_messages`, `ai_credit_balances`, `ai_credit_transactions`.
- **Expected:** 1 conversation, 2 messages (user + assistant), `credits_remaining` decremented, transaction with non-null model.
- **Observed (all passing):**
  - Conversation: `id=019d9afa-1f60-71bd-86e1-a866f5b5482d`, title="Simple Math Question".
  - Messages: `msg_count=2`, `roles=["user","assistant"]`, assistant content: `"4! 😄\n\nThat said, I'm specifically designed to help you manage your CRM data…"`.
  - Credits before: `remaining=500, used=0`. Credits after: `remaining=499, used=1`.
  - Last transaction: `model=claude-sonnet-4-6`, `charged=1`, `input_tokens=3451`, `output_tokens=57`.
  - Model name is correctly populated (not "unknown").
- **Root cause hypothesis:** N/A — nominal path working correctly.
- **Fix sketch:** N/A.

### F-014: Task 2.3 — Broadcast event names match client listeners; no mismatch

- **Surface:** `vendor/laravel/ai/src/Streaming/Events/` vs `packages/Chat/resources/views/livewire/chat/chat-interface.blade.php:184–187`
- **Severity:** P3 observability
- **Category:** static analysis
- **Steps to reproduce:**
  1. Check `TextDelta::toArray()['type']`, `StreamEnd::toArray()['type']`, `ToolResult::toArray()['type']`.
  2. Check `ConversationResolved::broadcastAs()`.
  3. Compare to `.listen()` calls in `chat-interface.blade.php`.
- **Expected:** Names match exactly.
- **Observed:** All four match:
  - SDK `TextDelta::type()` → `"text_delta"` ↔ `.listen('.text_delta', …)` ✓
  - SDK `StreamEnd::type()` → `"stream_end"` ↔ `.listen('.stream_end', …)` ✓
  - SDK `ToolResult::type()` → `"tool_result"` ↔ `.listen('.tool_result', …)` ✓
  - `ConversationResolved::broadcastAs()` → `"conversation.resolved"` ↔ `.listen('.conversation.resolved', …)` ✓
  - Note: `StreamEvent::broadcast()` uses `->as($this->type())` (no leading dot); Laravel Echo automatically prepends `.` when listening on private channels — so the dot-prefix in listener calls is correct.
- **Fix sketch:** N/A.

### F-015: Task 2.4 — `PendingActionService::createProposal()` crashes with `$user = null` in queued job context

- **Surface:** `ProcessChatMessage` job → `BaseWriteCreateTool::handle()` → `PendingActionService::createProposal()`
- **Severity:** P0 blocker
- **Category:** crash / data loss
- **Steps to reproduce:**
  1. Navigate to `/{slug}/chats/{conversationId}` with a live conversation.
  2. Send: "Please create a company called QA Test Corp with website qa-test.example."
  3. AI model decides to call `CreateCompanyTool`.
  4. `BaseWriteCreateTool::handle()` calls `auth()->user()` — returns `null` (no session in queued job).
  5. `PendingActionService::createProposal(user: null, …)` throws `TypeError`.
- **Expected:** `PendingAction` record created with `status=pending`, company NOT yet created, response returned to user.
- **Observed:**
  - Job UUID `62956f02-5860-47fc-8c96-2b0acbb1294f` landed in `failed_jobs` at `2026-04-17 10:26:57`.
  - Exception: `TypeError: PendingActionService::createProposal(): Argument #1 ($user) must be of type App\Models\User, null given`.
  - Stack: `BaseWriteCreateTool.php:40 → auth()->user()` returns `null`.
  - No `PendingAction` row created. Conversation has no new messages persisted (stays at 2). Credits NOT charged (`.then()` callback never ran).
  - Company NOT created (correct isolation, but flow is fully broken).
- **Root cause:** `BaseWriteCreateTool::handle()` uses `auth()->user()` to retrieve the current user. Queued jobs run in a CLI context with no HTTP session; the `web` auth guard has no authenticated user. The `ProcessChatMessage` job holds `$this->user` but does not bind it into the auth context before running the agent — tools inside the agent cannot access the user via `auth()`.
- **Fix sketch:** In `ProcessChatMessage::handle()`, bind the user into the auth guard before streaming:
  ```php
  auth()->setUser($this->user);
  ```
  Or pass `$user` explicitly into `BaseWriteCreateTool` via constructor injection using the agent's tool-resolution mechanism, rather than relying on `auth()->user()`.
- **Proposed Pest test (Phase 14):**
  ```php
  it('creates a pending action when AI proposes a company', function () {
      $user = User::factory()->withPersonalTeam()->create();
      $team = $user->currentTeam;
      // ensure credits available
      AiCreditBalance::factory()->for($team)->create(['credits_remaining' => 100]);

      Bus::fake();
      $response = actingAs($user)->postJson('/chat', ['message' => 'Create company Acme']);
      $response->assertOk();

      Bus::assertDispatched(ProcessChatMessage::class);
  });
  ```

### F-016: Task 2.5 — Dual-instance `handleConversationResolved` race and querystring-clobbering in URL rewrite regex

- **Surface:** `handleConversationResolved` in `chat-interface.blade.php:260–268`; side panel + full-page simultaneous mount
- **Severity:** P1 major
- **Category:** correctness / UX
- **Steps to reproduce (dual instance):**
  1. Open any page that renders both the Filament side panel (`@livewire('app.chat.chat-side-panel')`) and the full `ChatConversation` page — which is possible when navigating to `/{slug}/chats/{id}` while the side panel is also visible.
  2. Both `chat-interface` Alpine components mount and call `setupEchoListener()`.
  3. Both subscribe to `window.Echo.private('chat.{userId}')` and both register `.conversation.resolved` handlers.
  4. When `ConversationResolved` is broadcast, both handlers fire on the same `window`.
  5. Both call `history.replaceState(null, '', path)` with potentially different `path` values (side panel may have `conversationId = null`, full page may have a different `conversationId`).
- **Expected:** Only one `history.replaceState` call; URL set to the new `/{slug}/chats/{newId}`.
- **Observed (static analysis):** Two `chat-interface` instances are rendered simultaneously — one in `chat-conversation.blade.php:3-5` and one in `chat-side-panel.blade.php:89-93`. Both initialize Echo listeners on the same private channel. Last-write-wins is non-deterministic.
- **Steps to reproduce (querystring clobbering — variant of F-012):**
  1. Existing conversation URL: `/{slug}/chats/some-uuid?message=foo`.
  2. `conversation.resolved` fires (e.g., after a new message on the same page).
  3. Regex 1 `/\/chats\/.*$/` matches `"/chats/some-uuid?message=foo"` and replaces the entire suffix including the querystring.
  4. Result: `/{slug}/chats/{newId}` — querystring silently dropped.
  - This is usually the desired outcome, but it discards any legitimate querystring parameters that may be added in the future (bookmarked filters, etc.).
- **Root cause (dual instance):** No guard against multiple `chat-interface` instances subscribing to the same Echo channel.
- **Root cause (querystring):** Greedy regex `/\/chats\/.*$/` consumes `?` and beyond — correct for the existing conversationId case but a latent clobber risk; and neither regex handles `/chats?…` (bare chats with querystring, as shown in F-012).
- **Fix sketch:**
  1. Deduplicate listeners: use a module-level flag or a `window.__chatEchoListener` guard so only one `chat-interface` subscribes at a time.
  2. Fix URL rewrite to handle querystrings explicitly:
     ```js
     const pathOnly = window.location.pathname;
     const newPath = pathOnly
         .replace(/\/chats(\/[^?]*)?$/, '/chats/' + event.conversationId);
     history.replaceState(null, '', newPath);
     ```
- **Proposed Pest test (Phase 14):** JS unit test for the rewrite logic.
- **Screenshots:** `.context/screenshots/05-url-stuck-with-message-param.png`
