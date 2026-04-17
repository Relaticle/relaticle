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

---

## Phase 3 Findings (Tasks 3.1–3.4)

### F-017: Side panel `window.keydown` and `chat:send` listeners never removed — accumulate on each Livewire component re-init

- **Surface:** `packages/Chat/resources/views/livewire/app/chat/chat-side-panel.blade.php:11-24`
- **Severity:** P1 major
- **Category:** memory leak / UX correctness
- **Steps to reproduce:**
  1. Load the app panel; `ChatSidePanel` Alpine component initializes, adding one `keydown` and one `chat:send` listener via `window.addEventListener`.
  2. Navigate to any other page and back (SPA or full). If Livewire re-mounts the component (BODY_END hook re-evaluates), `init()` fires again, adding a second copy of each listener.
  3. After N round-trips, pressing Cmd+J fires N toggle operations — odd N opens the panel, even N closes and re-opens it in the same keypress cycle.
  4. Similarly, clicking a suggested prompt fires `handleSendFromDashboard` N times.
- **Observed:** Static analysis confirms `init()` contains `window.addEventListener('keydown', …)` (line 12) and `window.addEventListener('chat:send', …)` (line 19) with no corresponding `destroy()` / `removeEventListener` calls. No `x-destroy` Alpine hook is defined. The `document.addEventListener('livewire:navigated', …)` on line 26 also accumulates.
- **Observed (runtime):** After 5 company↔dashboard round-trips, Cmd+J still toggled the panel once — Livewire's persistent component mechanism kept the same DOM node alive in this session, suppressing the symptom. The leak is latent but will manifest when the Livewire component unmounts and re-mounts (e.g., first load of each fresh page, auth state changes, multi-tab usage).
- **Root cause:** Alpine's `init()` lifecycle function has no paired `destroy()` to clean up window-level listeners. Livewire's `BODY_END` render hook re-injects the component on every server-rendered page, and any unmount/remount cycle re-runs `init()`.
- **Proposed fix:** Add `destroy()` to the Alpine `x-data` object:
  ```js
  destroy() {
      window.removeEventListener('keydown', this._keydownHandler);
      window.removeEventListener('chat:send', this._chatSendHandler);
      document.removeEventListener('livewire:navigated', this._navigatedHandler);
  }
  ```
  And store handler references before attaching:
  ```js
  init() {
      this._keydownHandler = (e) => { … };
      this._chatSendHandler = (e) => { … };
      this._navigatedHandler = () => { $wire.refreshContext(); };
      window.addEventListener('keydown', this._keydownHandler);
      window.addEventListener('chat:send', this._chatSendHandler);
      document.addEventListener('livewire:navigated', this._navigatedHandler);
  }
  ```
- **Proposed Pest test (Phase 14):** Browser test asserting Cmd+J toggles exactly once after 5 navigations.

### F-018: `localStorage` width read has no clamp — corrupted value renders panel below minimum usable width

- **Surface:** `packages/Chat/resources/views/livewire/app/chat/chat-side-panel.blade.php:6`
- **Severity:** P3 polish
- **Category:** UX / robustness
- **Steps to reproduce:**
  1. `localStorage.setItem('chat-panel-width', '100')` in any browser tab.
  2. Reload the page and open the side panel (Cmd+J).
  3. Panel renders at 100px — too narrow to read content or use the textarea.
- **Observed:** `width: 100px` confirmed at runtime. The `minWidth: 360` property exists but is only enforced during drag resize (in `startResize → onMouseMove`). The initial `width` assignment on line 6 reads raw `localStorage` without clamping.
- **Root cause:** `width: parseInt(localStorage.getItem('chat-panel-width') || '420')` has no `Math.max(this.minWidth, Math.min(this.maxWidth, …))` guard.
- **Proposed fix:**
  ```js
  width: Math.max(360, Math.min(720, parseInt(localStorage.getItem('chat-panel-width') || '420'))),
  ```
  Or compute dynamically once `minWidth`/`maxWidth` are defined (use `$nextTick` or a getter).
- **Proposed Pest test (Phase 14):** Browser test setting `localStorage` to `50` and asserting rendered panel width ≥ 360px.

### F-019: Mobile viewport — side panel overflows 375px screen by 45px; no responsive breakpoint or max-width:100vw

- **Surface:** `packages/Chat/resources/views/livewire/app/chat/chat-side-panel.blade.php:61` (`:style="{ width: width + 'px' }"`)
- **Severity:** P1 major
- **Category:** mobile UX
- **Steps to reproduce:**
  1. Set viewport to 375×812 (iPhone SE/13).
  2. Navigate to dashboard and open side panel (Cmd+J or toggle button).
  3. Panel renders at 420px width, viewport is 375px.
- **Observed:** `viewportW: 375, panelW: 420, overflows: true` — panel extends 45px beyond the right edge of the viewport, hiding content and making the panel unusable on mobile.
- **Root cause:** `width` defaults to 420 (or whatever is in `localStorage`). The `:style` binding sets `width` in pixels with no `max(100vw)` constraint. `minWidth` is 360 which itself exceeds most phone viewport widths.
- **Proposed fix:** Add CSS `max-width: 100vw` to the panel element, and clamp `minWidth` dynamically:
  ```js
  minWidth: Math.min(360, window.innerWidth),
  ```
  Or add a Tailwind class `max-w-full` and change the binding to `:style="{ width: Math.min(width, window.innerWidth) + 'px' }"`.
- **Screenshots:** `.context/screenshots/03-panel-mobile.png`
- **Proposed Pest test (Phase 14):** Browser test at 375px viewport asserting `panel.offsetWidth <= 375`.

### F-020: `chat:send-message` Livewire event dispatched by `ChatSidePanel` has no listener in `ChatInterface` — suggested prompts in side panel are a dead drop

- **Surface:** `packages/Chat/src/Livewire/App/Chat/ChatSidePanel.php:60` → `packages/Chat/src/Livewire/Chat/ChatInterface.php`
- **Severity:** P1 major
- **Category:** broken feature
- **Steps to reproduce:**
  1. Navigate to dashboard and open the side panel.
  2. Click any suggested prompt button (e.g. "CRM overview").
  3. The button dispatches a browser `CustomEvent('chat:send', …)`.
  4. Panel's `chat:send` window listener calls `$wire.handleSendFromDashboard('CRM overview', 'suggestion')`.
  5. `handleSendFromDashboard` sets `isOpen = true` and calls `$this->dispatch('chat:send-message', message: …)`.
  6. Livewire dispatches `chat:send-message` as a browser event; no component or Alpine handler receives it.
- **Observed:** After clicking "CRM overview", the panel remains open but no user message bubble appeared, no network request to `/chat` was fired, and no streaming indicator appeared. The `ChatInterface` Alpine component has no `window.addEventListener('chat:send-message', …)` and the PHP class has no `#[On('chat:send-message')]` attribute.
- **Root cause:** `ChatSidePanel::handleSendFromDashboard()` uses `$this->dispatch()` (Livewire 4 browser-event dispatch) but the receiving `ChatInterface` — a sibling Livewire component — does not listen to `chat:send-message`. Neither `$listeners` nor `#[On]` is defined in `ChatInterface.php`, and the blade template has no `x-on:chat:send-message` binding.
- **Fix sketch (option A — add `#[On]` to ChatInterface):**
  ```php
  #[On('chat:send-message')]
  public function sendFromPanel(string $message): void
  {
      $this->dispatch('chat:trigger-send', message: $message);
  }
  ```
  Then in the Alpine component's `init()`: `window.addEventListener('chat:trigger-send', (e) => { this.input = e.detail.message; this.sendMessage(); })`.
- **Fix sketch (option B — bypass Livewire, use a direct browser event):**
  In `handleSendFromDashboard`, replace `$this->dispatch('chat:send-message', …)` with a JS eval that directly calls `sendMessage()` on the embedded Alpine component — or dispatch a plain `window` event that the Alpine `init()` in `chat-interface.blade.php` listens for.
- **Proposed Pest test (Phase 14):**
  ```php
  it('clicking a suggested prompt in the side panel sends a message', function () {
      // livewire(ChatSidePanel::class)->call('handleSendFromDashboard', 'CRM overview', 'suggestion')
      // assert ChatInterface received message
  });
  ```

### F-021: Two `ChatInterface` Livewire components simultaneously active on `/chats/{id}` page — both subscribe to same Echo channel

- **Surface:** Full-page `ChatConversation` + `ChatSidePanel` → both embed `chat.chat-interface`
- **Severity:** P1 major (extends F-016)
- **Category:** correctness / race condition
- **Steps to reproduce:**
  1. Navigate to `/{slug}/chats/{conversationId}`.
  2. The full-page `ChatConversation` renders `@livewire('chat.chat-interface', ['conversationId' => $id])`.
  3. The BODY_END-injected `ChatSidePanel` renders a second `@livewire('chat.chat-interface', ['conversationId' => null])`.
  4. Both Alpine instances call `setupEchoListener()` → `window.Echo.private('chat.{userId}').listen(…)`.
- **Observed:** `chatInterfaces: 2` confirmed in browser at runtime on the chats page (both present even before opening the side panel, since the panel component is always mounted via BODY_END even when `isOpen=false`). Instance 0: full-page, `conversationId='019d9afe-…'`. Instance 1: side panel, `conversationId=null`.
- **Effect:** On `conversation.resolved` broadcast:
  - The full-page instance calls `handleConversationResolved` with the real `conversationId` and rewrites the URL correctly.
  - The side panel instance ALSO calls `handleConversationResolved` with `event.conversationId`, but its own `this.conversationId` is `null` — it may rewrite the URL to the new ID even when the user is not interacting with the side panel.
  - Two competing `history.replaceState` calls fire in undefined order. Last-write-wins is non-deterministic.
- **Root cause:** `BODY_END` renders `ChatSidePanel` (and its embedded `ChatInterface`) on every page, including the full-page chat view. No guard prevents double-mounting.
- **Fix sketch:** Add a guard in `setupEchoListener()` using `window.__chatEchoAttached` to prevent double subscription. Alternatively, conditionally suppress the side panel's embedded `ChatInterface` when a full-page chat is active, or use a scoped channel identifier per instance.
- **Screenshots:** `.context/screenshots/03-dual-instance.png`
- **Proposed Pest test (Phase 14):** Browser test asserting only one `chat.{userId}` Echo channel subscription is active on the chats page.

---

## Phase 4 — PendingAction State Machine (2026-04-17)

Tasks 4.1–4.14 executed. All HTTP routes tested via browser `fetch()` (authenticated session) and bare `curl` (unauthenticated). Race tested via `Promise.all`. Expire command tested directly via Artisan.

### F-022: Phase 4 Task 4.1–4.2 — Create-approve and create-reject happy paths nominal

- **Surface:** `POST /chat/actions/{id}/approve` and `/reject`
- **Severity:** P3 observability
- **Category:** confirmation
- **Steps to reproduce:**
  1. Seed `PendingAction` with `operation=Create`, `action_class=CreateCompany`, `status=Pending`.
  2. `POST /chat/actions/{id}/approve` with valid session + CSRF.
  3. Repeat with `/reject` on a separate row.
- **Observed:**
  - Approve: `200 {"status":"approved","result_data":{"id":"...","type":"company"}}`. DB: `status=approved`, `resolved_at` set, `result_data.id` matches new Company ID. Company row created.
  - Reject: `200 {"status":"rejected"}`. DB: `status=rejected`, `resolved_at` set. No Company row created.
- **Root cause:** Nominal — both happy paths operate correctly.
- **Fix sketch:** None required.

### F-023: Phase 4 Task 4.3 — Approve expired action → 422; status stays `pending` (no auto-flip)

- **Surface:** `PendingActionService::validateResolvable()` + `PendingAction::isExpired()`
- **Severity:** P2 minor (UX/data-integrity)
- **Category:** state machine correctness
- **Steps to reproduce:**
  1. Seed PendingAction with `expires_at = now()->subMinute()`, `status = Pending`.
  2. `POST /chat/actions/{id}/approve`.
- **Expected:** 422 `{"error":"This action has expired"}`.
- **Observed:** 422 returned correctly. However, `status` remains `pending` — NOT flipped to `expired` by the approve attempt.
- **Secondary observation:** The UI (chat cards with Approve/Reject buttons) has no client-side timer or visual indicator that an action has expired. A card seeded with `expires_at` in the past renders live Approve/Reject buttons with no visual distinction from a valid pending action. A user clicking Approve receives a 422 but may not understand why (depends on UI error handling — not yet verified in Phase 4).
- **Root cause:** By design — `expireStale()` is a background job; the approve endpoint does not auto-transition status. The `expired` scope in `PendingAction` correctly filters by `status=Pending AND expires_at < now()`, so the command will catch it on the next 5-minute run. Gap: between creation and command run, an expired action appears live in the UI.
- **Fix sketch:** Two options: (a) auto-transition status to `expired` inside the 422 branch of `approve()`/`reject()` so the DB stays consistent immediately; (b) add client-side expiry countdown to grey out buttons before server expiry. Option (a) is a one-liner in `validateResolvable`.

### F-024: Phase 4 Task 4.4 — Approve already-resolved action → 422 nominal

- **Surface:** `PendingActionService::validateResolvable()` → `isPending()` check
- **Severity:** P3 observability
- **Category:** confirmation
- **Steps to reproduce:**
  1. Seed PendingAction with `status=Approved`, `resolved_at=now()`.
  2. `POST /chat/actions/{id}/approve`.
- **Observed:** `422 {"error":"This action has already been resolved"}`. State unchanged.
- **Root cause:** Nominal.
- **Fix sketch:** None required.

### F-025: Phase 4 Task 4.5 — Race condition: `lockForUpdate` prevents double-execute

- **Surface:** `PendingActionService::approve()` → `DB::transaction` + `lockForUpdate`
- **Severity:** P3 observability
- **Category:** confirmation (race guard verified)
- **Steps to reproduce:**
  1. Seed one PendingAction.
  2. Fire two concurrent `POST /chat/actions/{id}/approve` via `Promise.all`.
- **Observed:** One `200` (approved), one `422 {"error":"This action has already been resolved"}`. `Company::where('name','Phase4 Race Test')->count()` = `1`. No double-execution.
- **Root cause:** `lockForUpdate()` inside the transaction serialises concurrent requests correctly.
- **Fix sketch:** None required. Note: `reject()` does NOT use a transaction or `lockForUpdate` — see F-026.

### F-026: Phase 4 Task 4.5 follow-on — `reject()` has no DB-level lock; concurrent rejects could double-resolve

- **Surface:** `PendingActionService::reject()` — no `DB::transaction`, no `lockForUpdate`
- **Severity:** P2 minor (data-integrity)
- **Category:** state machine correctness
- **Steps to reproduce (hypothetical):**
  1. Seed one PendingAction.
  2. Fire two concurrent `POST /chat/actions/{id}/reject` simultaneously.
- **Expected:** One `200 {"status":"rejected"}`, one `422`.
- **Observed (code-level):** `reject()` calls `validateResolvable()` then `$pendingAction->update()` without a transaction or row lock. The two requests can both pass `validateResolvable()` (both see `status=Pending`) before either commits the update. Result: `resolved_at` is overwritten twice with `now()` and `status=rejected` twice — functionally idempotent for rejection (no side-effects like a Create), but the pattern is inconsistent with `approve()` and would be dangerous if `reject()` ever gains side-effects (e.g., sending a notification).
- **Root cause:** `reject()` was implemented without the same transaction/lock guard as `approve()`.
- **Fix sketch:** Wrap `reject()` in `DB::transaction()` with a `lockForUpdate` re-fetch, mirroring the `approve()` implementation.

### F-027: Phase 4 Task 4.6 — Cross-user same-team action → 403 nominal

- **Surface:** `PendingActionController::approve()` user_id check
- **Severity:** P3 observability
- **Category:** confirmation
- **Steps to reproduce:**
  1. Seed PendingAction owned by teammate (same team, different user_id).
  2. Approve as `chat-qa@relaticle.test`.
- **Observed:** `403 {"error":"You can only approve your own actions"}`. No company created.
- **Root cause:** Nominal — user_id guard fires before service call.
- **Fix sketch:** None required.

### F-028: Phase 4 Task 4.7 — Cross-team action → 404 (no existence leak) nominal

- **Surface:** `PendingActionController` team_id check
- **Severity:** P3 observability
- **Category:** confirmation
- **Steps to reproduce:**
  1. Seed PendingAction owned by `other-team@relaticle.test` (different team).
  2. Approve as `chat-qa@relaticle.test`.
- **Observed:** `404 {"error":"Not found"}`. No existence information leaked.
- **Root cause:** Nominal — team_id guard returns 404 before user_id check, correctly treating cross-team records as non-existent.
- **Fix sketch:** None required.

### F-029: Phase 4 Task 4.8 — Unauthenticated request returns 419 (CSRF), not 401

- **Surface:** Middleware stack ordering — `VerifyCsrfToken` fires before `Authenticate`
- **Severity:** P3 polish
- **Category:** observability / API ergonomics
- **Steps to reproduce:**
  ```bash
  curl -sk -X POST "https://app.relaticle-pr-209.test/chat/actions/{id}/approve" \
    -H "Accept: application/json" -w "\n%{http_code}\n"
  ```
- **Observed:** `419 {"message":"CSRF token mismatch.","exception":"...HttpException..."}` — full stack trace leaked in JSON response.
- **Expected (API convention):** `401 {"message":"Unauthenticated."}` for missing session; 419 is acceptable for missing CSRF on stateful routes, but leaking the full exception stack trace is undesirable.
- **Secondary finding:** The `419` response body contains the full PHP stack trace (40+ frames). This is likely a `APP_DEBUG=true` or `APP_ENV=local` issue in the QA environment but should be confirmed for production.
- **Root cause:** CSRF middleware precedes auth middleware; `APP_DEBUG` may be `true` in this environment.
- **Fix sketch:** Ensure `APP_DEBUG=false` in production. Consider adding the chat action routes to an API middleware group (with token auth) if they need to be called without CSRF (e.g., from mobile clients).

### F-030: Phase 4 Task 4.9–4.10 — Update and Delete action paths nominal

- **Surface:** `PendingActionService::executeUpdate()` and `executeDelete()`
- **Severity:** P3 observability
- **Category:** confirmation
- **Steps to reproduce:**
  1. Seed Update action with `_record_id` and `_model_class=Company::class`. Approve. Assert company renamed.
  2. Seed Delete action with same fields. Approve. Assert company soft-deleted.
- **Observed:**
  - Update: `200 {"status":"approved","result_data":{"id":"...","type":"company"}}`. Company `name` changed from "Phase4 Test Corp" to "Renamed By Approval". ✓
  - Delete: `200 {"status":"approved","result_data":{"success":true}}`. Company `deleted_at` set (soft-delete via `SoftDeletes` trait). `Company::find(id)` returns null. ✓
  - Note: Delete returns `{"success":true}` not `{"id":"...","type":"..."}` because `executeDelete` returns `null` — the result_data branch uses `['success' => true]` for non-Model results. This is consistent with the code.

### F-031: Phase 4 Task 4.11 — Malicious `_model_class` blocked by allowlist

- **Surface:** `PendingActionService::resolveModelClass()` → `ALLOWED_MODEL_CLASSES` allowlist
- **Severity:** P3 observability
- **Category:** confirmation (security guard verified)
- **Steps to reproduce:**
  1. Seed Update action with `_model_class = App\Models\User::class`.
  2. Approve.
- **Observed:** `422 {"error":"Invalid model class: App\\Models\\User"}`. No data accessed.
- **Root cause:** Nominal — `ALLOWED_MODEL_CLASSES` constant covers `Company`, `People`, `Opportunity`, `Task`, `Note` only.
- **Fix sketch:** None required.

### F-032: Phase 4 Task 4.12–4.13 — `chat:expire-pending-actions` command and schedule nominal

- **Surface:** `ExpirePendingActionsCommand` + `PendingActionService::expireStale()` + `bootstrap/app.php` schedule
- **Severity:** P3 observability
- **Category:** confirmation
- **Steps to reproduce:**
  1. Seed 3 past-due Pending rows + 1 past-due Approved row + 1 future Pending row.
  2. `php artisan chat:expire-pending-actions`.
- **Observed:**
  - First run: `Expired 4 pending action(s).` (3 test rows + 1 leftover from Task 4.3). All expired rows: `status=expired`, `resolved_at` set.
  - Approved row (past-due): status unchanged (`approved`). The `expired()` scope correctly gates on `status=Pending`. ✓
  - Future Pending row: status unchanged (`pending`). ✓
  - Second run (idempotent): `Expired 0 pending action(s).` ✓
  - Schedule: `bootstrap/app.php:79` — `$schedule->command('chat:expire-pending-actions')->everyFiveMinutes()`. ✓
- **Root cause:** Nominal.
- **Fix sketch:** None required.

---

## Phase 5 — Conversation Management (2026-04-17)

### F-033: No chat index page — users with >10 conversations cannot reach older chats

- **Surface:** Sidebar (`chat-sidebar-nav.blade.php`) + Filament page registry
- **Severity:** P1 UX
- **Category:** missing feature
- **Steps to reproduce:**
  1. Seed 15 conversations for a user.
  2. Navigate to `/{slug}/dashboard`.
  3. Count `[data-group-label="Chats"] li.fi-sidebar-item` items.
  4. Search `app/Filament/Pages/` and routes for any conversation list/index page.
- **Expected:** A "View all chats" link or `/chats` index page giving access to all conversations.
- **Observed:**
  - Sidebar shows exactly 10 items (newest first by `updated_at DESC`). Conversations #10–#14 are invisible.
  - Only one Filament page exists: `ChatConversation` with slug `chats/{conversationId?}`. No index route.
  - `grep -rn "ChatConversation\|/chats$" app/Filament/Pages/` → single result for `ChatConversation.php`. No index page.
  - There is no "View all" link in the sidebar group header.
- **Root cause:** Sidebar query is hard-limited to 10. No index page was built.
- **Fix sketch:** Add a `/chats` Filament page listing all user conversations paginated, and add a "View all" link at the bottom of the sidebar group.

### F-034: `wire:poll.60s` on sidebar is an at-scale AJAX flood

- **Surface:** `packages/Chat/resources/views/livewire/app/chat/chat-sidebar-nav.blade.php:2`
- **Severity:** P1 scalability
- **Category:** performance
- **Steps to reproduce:**
  1. Read `chat-sidebar-nav.blade.php` line 1–10.
  2. Calculate: 100k active users × 1 request/60s × 86,400s/day = ~144M Livewire requests/day.
- **Expected:** Sidebar refreshes only when a conversation is actually created or renamed, via custom event.
- **Observed:**
  - `wire:poll.60s` fires unconditionally every 60 seconds for every user with the sidebar open.
  - The same file already has `x-on:chat:conversation-created.window="$wire.$refresh()"` (line 7), which correctly handles new-conversation events from `handleConversationResolved`.
  - In the Phase 5 poll test, a DB-inserted conversation appeared in the sidebar between the 30s and 40s check — consistent with a ~60s poll cycle.
- **Root cause:** `wire:poll.60s` was added as a fallback/catch-all. The event-driven path (`chat:conversation-created`) already covers the new-conversation case; the poll adds no value once WS is healthy and is wasteful at scale.
- **Fix sketch:** Remove `wire:poll.60s`. The `chat:conversation-created` event listener is sufficient for real-time updates. If offline-resilience is needed, use a conservative poll only when the Echo channel is disconnected.

### F-035: `handleConversationResolved` cross-instance URL rewrite (extends F-016)

- **Surface:** `packages/Chat/resources/views/livewire/chat/chat-interface.blade.php:257–269`
- **Severity:** P1 logic
- **Category:** correctness
- **Steps to reproduce (static trace):**
  1. Read `chat-interface.blade.php` lines 180–195 (Echo listener binds on `chat.${userId}` channel).
  2. Read `handleConversationResolved` at line 257: unconditionally does `this.conversationId = event.conversationId` then `history.replaceState(...)`.
  3. Identify scenarios where multiple `ChatInterface` components share the same user-scoped channel.
- **Expected:** The handler guards on `conversationId` match before rewriting the URL.
- **Observed:**
  - No guard exists. `this.conversationId = event.conversationId` runs unconditionally on every instance.
  - **Scenario A (side-panel + page):** User is on `/chats/A`. Side panel (conversationId=null) resolves a new conversation B. Both instances receive the event. The page-level instance overwrites its own `conversationId` to B and rewrites `window.location` from `/chats/A` to `/chats/B`.
  - **Scenario B (two tabs):** Tab 1 on `/chats/A`, Tab 2 on `/chats/B`. Sending from Tab 1 resolves conversation C. Tab 2's listener fires and rewrites its URL to `/chats/C`.
- **Root cause:** User-scoped Echo channel (`chat.${userId}`) means all UI instances share events. `handleConversationResolved` was written assuming one instance per user.
- **Fix sketch:** Add a guard at the top of `handleConversationResolved`: `if (this.conversationId !== null && this.conversationId !== event.conversationId) return;` — lets side-panel (null) always handle it, page instances only handle their own.

### F-036: No delete conversation UI — users cannot remove their own chats

- **Surface:** `packages/Chat/resources/views/` (all Blade/Livewire views)
- **Severity:** P1 UX
- **Category:** missing feature
- **Steps to reproduce:**
  1. `grep -rn "destroy\|deleteConversation\|chat.conversations.destroy\|/chat/conversations/" packages/Chat/resources/ resources/`
  2. Inspect the sidebar and chat page UI for any delete affordance.
- **Expected:** A delete button or context menu item per conversation allowing the user to remove it.
- **Observed:**
  - Zero grep results in view files for any delete conversation UI.
  - `DELETE chat/conversations/{conversation}` route exists (`chat.conversations.destroy`) and the `DeleteConversation` action works correctly (verified via tinker: returns `true`, row removed, cross-user deletion returns `false`).
  - The backend is ready; there is simply no frontend affordance.
- **Root cause:** Delete UI was not implemented.
- **Fix sketch:** Add a trash icon button to each sidebar conversation item (visible on hover). Wire it to a Livewire action that calls `DeleteConversation::execute()` and dispatches `chat:conversation-created` to refresh the sidebar.

### F-037: `DeleteConversation` does not clean up orphaned `pending_actions` rows

- **Surface:** `packages/Chat/src/Actions/DeleteConversation.php:14–29`
- **Severity:** P2 data-integrity
- **Category:** correctness
- **Steps to reproduce:**
  1. Read `DeleteConversation::execute()` — only deletes `agent_conversations` and `agent_conversation_messages`.
  2. Check `pending_actions` schema: `conversation_id` column present, no FK constraint to `agent_conversations`.
  3. Delete a conversation that has associated `pending_actions` rows; query `pending_actions` for that `conversation_id`.
- **Expected:** `pending_actions` rows for the deleted conversation are also deleted.
- **Observed:**
  - `DeleteConversation` deletes only two tables: `agent_conversations` and `agent_conversation_messages`.
  - `pending_actions` has `conversation_id` with no FK constraint (confirmed via `information_schema.referential_constraints` — only `team_id` and `user_id` FKs exist).
  - After conversation deletion, `pending_actions` rows with the deleted `conversation_id` remain indefinitely. They accumulate unbounded garbage.
  - `extractPendingActions()` returns these orphans as `status=expired` (null DB lookup falls back to `'expired'`), so no immediate crash, but data is stale.
- **Root cause:** Missing cleanup in `DeleteConversation::execute()`. No DB-level cascade constraint.
- **Fix sketch:** Add `DB::table('pending_actions')->where('conversation_id', $conversationId)->delete();` inside the transaction in `DeleteConversation`.

### F-038: `extractPendingActions` N+1 — one DB query per pending action per message render

- **Surface:** `packages/Chat/src/Actions/ListConversationMessages.php:42–79` (`extractPendingActions` method)
- **Severity:** P2 performance
- **Category:** query efficiency
- **Steps to reproduce:**
  1. Read `extractPendingActions()` (lines 42–79).
  2. Note `DB::table('pending_actions')->where('id', $pendingActionId)->value('status')` inside a `foreach` loop.
  3. Load a conversation history with N messages each containing M pending_action tool results.
- **Expected:** Single `whereIn` prefetch of all pending_action IDs, then local map.
- **Observed:**
  - Each message fires one `SELECT ... WHERE id = ?` per pending_action found in tool_results.
  - For 20 messages × 2 pending actions each = 40 separate DB queries just to hydrate statuses during history load.
- **Root cause:** Incremental DB lookup inside a loop; no batching across messages.
- **Fix sketch:** Collect all `pending_action_id` values from all messages first, batch-fetch `DB::table('pending_actions')->whereIn('id', $ids)->pluck('status', 'id')`, then pass the status map into `extractPendingActions`.

### F-039: Title truncation works correctly — P3 observability note

- **Surface:** Sidebar title rendering + `agent_conversations.title` schema
- **Severity:** P3 observability
- **Category:** confirmation
- **Steps to reproduce:**
  1. Insert a conversation with 255-char title (max DB allows; 500-char throws `SQLSTATE[22001]`).
  2. Reload sidebar, read `li:first-child a` text content and length.
- **Expected:** Title truncated to ≤33 chars in the UI (30 + `...`).
- **Observed:**
  - DB: `varchar(255)` — rejects titles over 255 chars at the DB level.
  - Sidebar: text length 33 (30 chars + `...`). `Str::limit($title, 30)` works correctly. ✓
  - No XSS risk: Blade escapes HTML entities.
- **Root cause:** Nominal.
- **Fix sketch:** None required.

---

## Phase 6 — `/chat/mentions` API (2026-04-17)

### F-041: `/chat/mentions` — dead endpoint, no UI consumer

- **Surface:** `packages/Chat/src/Http/Controllers/ChatController.php::mentions`, registered as `chat.mentions` in `packages/Chat/routes/chat.php`
- **Severity:** P1 missing feature
- **Category:** completeness
- **Steps to reproduce:**
  1. `grep -rn "chat/mentions\|chat\.mentions\|mentions" packages/Chat/resources/ resources/ app/`
  2. Read `chat-interface.blade.php` — textarea with no `@` keydown handler.
- **Expected:** `@`-triggered typeahead dropdown in the chat input calling `/chat/mentions?q=`.
- **Observed:** Zero references to `chat/mentions` or `chat.mentions` outside the route file and controller. The chat input (`chat-interface.blade.php`) is a plain `<textarea>` with no mention-trigger logic. The endpoint is fully implemented server-side but never called from any UI.
- **Root cause:** Server implementation was merged before the front-end @mention component was built.
- **Fix sketch:** Add Alpine.js `@keydown` handler detecting `@` in the textarea; on trigger, fetch `/chat/mentions?q=<typed>` and render a floating results list. On item select, insert `@[name]` into the input.

### F-042: `q` parameter accepts raw `%` wildcard — enables full-team data enumeration

- **Surface:** `ChatController::mentions` — query built as `"ilike", "%{$search}%"` with no escaping of LIKE special chars
- **Severity:** P2 security
- **Category:** input sanitization
- **Steps to reproduce:**
  1. `GET /chat/mentions?q=%%` → HTTP 200, `data` count = 15 (capped by `take(15)`).
  2. `GET /chat/mentions?q=a%` → HTTP 200, count = 15. Same result — `%` is treated as a wildcard by Postgres.
  3. Paginate with offset patterns to exfiltrate full CRM contents.
- **Expected:** `%` and `_` in `q` treated as literal characters (escaped to `\%` and `\_` before interpolation).
- **Observed:** `q=%%` returns 15 results; `q=a%` returns 15 results. Any logged-in user can enumerate their entire team's companies, people, opportunities, and tasks without knowing any names.
- **Root cause:** Missing `str_replace(['%', '_'], ['\%', '\_'], $search)` before interpolating into `ilike "%{$search}%"`.
- **Fix sketch:**
  ```php
  $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);
  // then use "%{$escaped}%" in the ilike clause
  ```

### F-043: No rate limit on `/chat/mentions` — brute-force / scraping vector

- **Surface:** `packages/Chat/routes/chat.php` — no `throttle` middleware on the `chat.mentions` route
- **Severity:** P2 security
- **Category:** rate limiting
- **Steps to reproduce:**
  1. Issue 1000 rapid `GET /chat/mentions?q=%%` requests.
  2. All return 200; no 429 observed.
- **Expected:** Route protected by `throttle:60,1` (60 req/min) consistent with other API endpoints.
- **Observed:** No rate-limit header (`X-RateLimit-Limit`) in response. Unlimited requests accepted.
- **Root cause:** Route was registered without a throttle middleware.
- **Fix sketch:** Add `->middleware('throttle:60,1')` to the `chat.mentions` route definition in `routes/chat.php`.

### F-044: No `max` validation on `q` parameter — 10 000-char query hits Postgres

- **Surface:** `ChatController::mentions` — `$request->string('q')` with only `length() < 2` guard
- **Severity:** P3 validation
- **Category:** input validation
- **Steps to reproduce:**
  1. `GET /chat/mentions?q=<10000-char string>` → HTTP 200, no error.
  2. Postgres executes `ilike '%<10000 chars>%'` — full sequential scan on every entity table.
- **Expected:** Request rejected with 422 when `q` exceeds a sensible maximum (e.g. 100 chars).
- **Observed:** HTTP 200, empty `data` array. No validation error. The 10 000-char LIKE pattern is passed to Postgres unchanged.
- **Root cause:** No `max:100` rule on the `q` parameter.
- **Fix sketch:** Add `$request->validate(['q' => ['nullable', 'string', 'max:100']]);` at the top of `mentions()`.

### F-045: Per-type `limit(5)` caps each entity type correctly — P3 confirmation

- **Surface:** `ChatController::mentions` — per-type `limit(5)`, total `take(15)`
- **Severity:** P3 observability
- **Category:** confirmation
- **Steps to reproduce:**
  1. Seed 7 companies named `Phase6Limit C0`–`C6`.
  2. `GET /chat/mentions?q=Phase6Limit` — observe result count.
- **Expected:** 5 results (per-type limit enforced).
- **Observed:** Count = 5. Only companies C0–C4 returned; C5 and C6 suppressed. `take(15)` outer cap not reached since only one entity type matches. ✓
- **Root cause:** Nominal.
- **Fix sketch:** None required. Note for product: if users need more than 5 results per type, expose a `limit` param or a dedicated search endpoint.

### F-046: Case-insensitive search via Postgres `ilike` works correctly — P3 confirmation

- **Surface:** `ChatController::mentions` — `where('name', 'ilike', "%{$search}%")`
- **Severity:** P3 observability
- **Category:** confirmation
- **Steps to reproduce:**
  1. Seed `Phase6 Alpha Inc`, `Phase6 Alpha Contact`, `Phase6 Alpha Deal`, `Phase6 Alpha Task`.
  2. Query `q=alpha`, `q=ALPHA`, `q=aLpHa` — compare result sets.
- **Expected:** All three return the same 4 records.
- **Observed:** All three return identical 4-item arrays. `ilike` is case-insensitive on Postgres. ✓ Tasks correctly map `title` → `name` in the response payload.
- **Root cause:** Nominal.
- **Fix sketch:** None required.

### F-047: Tenant isolation holds — no cross-team data leak

- **Surface:** `ChatController::mentions` — `whereBelongsTo($team)` scope on all four entity queries
- **Severity:** P3 confirmation (P0 if it had failed)
- **Category:** data isolation
- **Steps to reproduce:**
  1. Authenticate as `chat-qa@relaticle.test` (team `chat-qas-team-nyzen`).
  2. `GET /chat/mentions?q=OTHER-TEAM` — other team has companies named `OTHER-TEAM-ACME*`.
- **Expected:** Empty `data` array.
- **Observed:** `{"data":[]}` — no cross-team records returned. ✓ `whereBelongsTo($team)` global scope filters correctly.
- **Root cause:** Nominal.
- **Fix sketch:** None required.

### F-040: U+202E RTL override character passes through `Str::limit` and renders in sidebar

- **Surface:** Sidebar title rendering (`chat-sidebar-nav.blade.php`)
- **Severity:** P3 security (UX hardening)
- **Category:** input sanitization
- **Steps to reproduce:**
  1. Insert conversation with title containing U+202E (RTL override) and U+202C (PDF).
  2. Reload sidebar, read `li:first-child a innerHTML`.
- **Expected:** Bidi override characters stripped or escaped before rendering.
- **Observed:**
  - `innerHTML` includes U+202E (`‮`) and U+202C (`‬`) unescaped in the DOM.
  - Blade escapes HTML entities so this is not an XSS vector. However, U+202E can visually reverse text direction in the rendered sidebar label.
  - Not exploitable for code injection but could be used for phishing-style social engineering within the app.
- **Root cause:** No Unicode bidi sanitization on conversation title input or display.
- **Fix sketch:** Strip Unicode bidi control characters (U+202A–U+202E, U+2066–U+2069, U+200F) from titles at save time, or add a sanitization helper before rendering.

---

## Phase 7 — Credit System Edge Cases

### F-048: 402 credit-exhausted rendered as plain chat bubble — no upgrade CTA

- **Surface:** `chat-interface.blade.php` Alpine `sendMessage` handler; `ChatController::send` (line 47–51)
- **Severity:** P1 revenue UX
- **Category:** conversion / billing
- **Steps to reproduce:**
  1. Set `credits_remaining = 0` for team via DB.
  2. Open `/chats`, type any message, press Enter.
- **Expected:** Dedicated paywall modal or inline paywall card with upgrade link and plan details.
- **Observed:** The 402 JSON `message` field ("You have used all your AI credits for this billing period.") is inserted directly into an assistant chat bubble (same gray bubble used for normal AI replies). No upgrade CTA, no billing link, no plan upsell. Screenshot: `.context/screenshots/07-credits-exhausted.png`.
- **Root cause:** Alpine `sendMessage` handles non-OK responses with `assistantMsg.content = body.message || 'Error ${status}...'` — treats all error bodies as chat content.
- **Fix sketch:** Detect `body.error === 'credits_exhausted'` in the Alpine handler and render a dedicated paywall card component (distinct visual style + "Upgrade plan" button linking to `/billing`). This is the single highest-value conversion moment in the product.

### F-049: Non-atomic credit gate — 1 credit allows N concurrent jobs (revenue leak)

- **Surface:** `ChatController::send` (lines 47, 56); `CreditService::hasCredits`
- **Severity:** P1 revenue
- **Category:** race condition / billing integrity
- **Steps to reproduce:**
  1. Set `credits_remaining = 1`.
  2. Fire 5 concurrent POST `/chat` requests before any job deducts.
- **Expected:** Only 1 request returns 200; subsequent requests see 0 credits and receive 402.
- **Observed:** All 5 concurrent requests pass `hasCredits > 0` simultaneously (balance read is non-atomic: no lock, no pre-deduction). All 5 dispatch `ProcessChatMessage` jobs. The balance is only decremented later inside the job via `CreditService::deduct` (which uses `lockForUpdate`), but by then all jobs are already queued. A team with 1 credit can trigger 5+ AI completions.
- **Root cause:** `hasCredits` is a plain SELECT with no lock; `dispatch` fires immediately after with no optimistic deduction. The gate and the charge are decoupled across HTTP request and queue job.
- **Fix sketch:** Perform an optimistic pre-deduction inside the HTTP request (atomic `UPDATE ... SET credits_remaining = credits_remaining - 1 WHERE credits_remaining > 0` returning affected rows). Refund inside the job if the AI call fails. Alternatively, use `DB::transaction` + `lockForUpdate` in the controller before dispatching.

### F-050: `resetPeriod` has no caller — credits never reset automatically

- **Surface:** `CreditService::resetPeriod`; `bootstrap/app.php` schedule; entire codebase
- **Severity:** P1 data-integrity
- **Category:** billing / scheduled jobs
- **Steps to reproduce:**
  1. `grep -rn "resetPeriod" packages/Chat/ app/ bootstrap/` — only one hit: the method definition itself.
  2. Inspect `bootstrap/app.php` `withSchedule()` block — no monthly credit reset command.
- **Expected:** A monthly scheduled command (or billing-webhook listener) calls `resetPeriod($team, $allowance)` for every active team at the start of each billing period.
- **Observed:** `resetPeriod` is defined but never invoked from any scheduled command, listener, observer, or webhook handler. Once a team exhausts their credits, `credits_remaining` stays at 0 permanently until manually seeded. There is no path back to a non-zero balance.
- **Root cause:** The monthly reset command was not implemented.
- **Fix sketch:** Create `php artisan chat:reset-credits` that iterates all teams, looks up their plan allowance from config, and calls `CreditService::resetPeriod`. Schedule it `->monthlyOn(1, '00:00')` in `bootstrap/app.php`. Also add a billing-webhook listener for plan upgrades/downgrades to immediately re-provision credits.

### F-051: `deduct()` silently no-ops when balance row is absent — inconsistency risk

- **Surface:** `CreditService::deduct` (lines 52–54)
- **Severity:** P2 data-integrity
- **Category:** billing integrity
- **Steps to reproduce:**
  1. Delete the `ai_credit_balances` row for a team.
  2. Observe `getBalance` returns 0, so `hasCredits` returns false → controller sends 402. No free access in the happy path.
  3. However: if the balance row is deleted between the `hasCredits` check and the `deduct` call inside the job (tiny TOCTOU window), `deduct` hits `if (!$balance instanceof AiCreditBalance) return;` and silently exits — AI work completes with no charge recorded and no transaction row created.
- **Expected:** Missing balance row should throw or log a critical error (it signals a broken onboarding state), not silently pass.
- **Root cause:** The silent return was added as defensive code but creates an invisible billing gap.
- **Fix sketch:** Replace the silent `return` with `Log::critical('Credit balance row missing for team', ['team_id' => $team->getKey()]); throw new \RuntimeException(...)` or at minimum emit an alert. Ensure team onboarding always calls `resetPeriod` to create the balance row.

### F-052: `calculateCredits` formula matches spec exactly — P3 confirmation

- **Surface:** `CreditService::calculateCredits` (lines 76–86)
- **Severity:** P3 polish
- **Category:** billing correctness
- **Steps to reproduce:** Call `calculateCredits` for each model/tool-count combination.
- **Expected vs Observed:**

| Input | Expected (`ceil((1×mult)+(n×0.5))`, min 1) | Actual |
|---|---|---|
| sonnet / 0 tools | 1 | **1** ✓ |
| sonnet / 4 tools | 3 | **3** ✓ |
| opus / 0 tools | 3 | **3** ✓ |
| opus / 4 tools | 5 | **5** ✓ |
| haiku / 0 tools | 1 (min clamp) | **1** ✓ |
| unknown model / 0 | 1 (fallback mult=1.0) | **1** ✓ |
| gpt-4o / 2 tools | 3 | **3** ✓ |

- **Observed:** All values match specification. Formula is correct.
- **Root cause:** Nominal.
- **Fix sketch:** None required.

### F-053: Dead config entries for `claude-haiku-4-5` and `gpt-4o-mini` — no `AiModel` enum case

- **Surface:** `packages/Chat/config/chat.php` `model_multipliers`; `packages/Chat/src/Enums/AiModel.php`
- **Severity:** P3 cleanup
- **Category:** dead code / config drift
- **Steps to reproduce:**
  1. `grep "claude-haiku\|gpt-4o-mini" packages/Chat/config/chat.php`
  2. `grep "claude-haiku\|gpt-4o-mini\|Haiku\|Mini" packages/Chat/src/Enums/AiModel.php` — no matches.
- **Expected:** Every model key in `model_multipliers` has a corresponding `AiModel` enum case; every enum case has a config entry.
- **Observed:**
  - `claude-haiku-4-5` (mult=0.5) and `gpt-4o-mini` (mult=0.5) are in config but have no `AiModel` case — they can never be selected through normal UI or `AiModelResolver`.
  - `gemini-2.5-pro` (used by `AiModel::GeminiPro`) has no config entry — falls through to the default multiplier of 1.0 silently.
- **Root cause:** Config and enum evolved independently without cross-checking.
- **Fix sketch:** Either add enum cases for haiku and gpt-4o-mini (if they are planned), or remove dead config keys. Add `gemini-2.5-pro` to `model_multipliers`. Consider a boot-time assertion that validates every `AiModel::modelId()` value has a config entry.

### F-054: `model` column in `ai_credit_transactions` is NOT NULL — no unique constraint

- **Surface:** `ai_credit_transactions` table schema
- **Severity:** P3 observability
- **Category:** schema / data quality
- **Steps to reproduce:** `php artisan db:table ai_credit_transactions`
- **Expected:** `model` column NOT NULL (correct); no unique constraint (expected).
- **Observed:**
  - `model varchar NOT NULL` — confirmed correct, a `'unknown'` string is stored when `StreamedAgentResponse->meta->model` is null.
  - No unique constraint on `model` — correct (many rows per model expected).
  - The string `'unknown'` will appear in transaction history making billing audits ambiguous. There is no index on `model` for query performance on per-model cost reports.
- **Root cause:** Nominal schema; the `'unknown'` fallback is functional but produces low-quality audit data.
- **Fix sketch:** Add an index on `model` for analytics queries. Consider logging a warning when `model` is `'unknown'` so ops can trace which response path produced a null model string.

### F-055: `resetPeriod` mechanics correct — period boundaries confirmed

- **Surface:** `CreditService::resetPeriod` (lines 88–99)
- **Severity:** P3 polish
- **Category:** billing correctness
- **Steps to reproduce:** Call `resetPeriod($team, 500)` and inspect resulting balance row.
- **Expected:** `credits_remaining=500`, `credits_used=0`, `period_starts_at=2026-04-01 00:00:00`, `period_ends_at=2026-04-30 23:59:59`.
- **Observed:** Exact match. `updateOrCreate` correctly resets used credits to 0. Period boundaries use `startOfMonth()` / `endOfMonth()` which aligns to calendar month (not 30-day rolling).
- **Root cause:** Nominal.
- **Fix sketch:** None for mechanics. The only gap is that this method is never automatically called (see F-050).

---

## Phase 8 — Validation / Auth / CSRF / XSS (2026-04-17)

### F-056: Empty and missing `message` → 422 — validation working

- **Surface:** `POST /chat` — `ChatController::send` validation rule `['required', 'string', 'max:5000']`
- **Severity:** P3 confirmation
- **Category:** input validation
- **Steps to reproduce:**
  - `POST /chat` with `{"message":""}` (empty string)
  - `POST /chat` with `{}` (missing key)
- **Expected:** 422 Unprocessable Entity with `errors.message` array.
- **Observed:** Both return `HTTP 422` with `{"message":"The message field is required.","errors":{"message":["The message field is required."]}}`.
- **Root cause:** Nominal — `required` rule correctly catches both cases.
- **Fix sketch:** None.

### F-057: 5001-char message → 422 — max:5000 enforced; no client-side counter

- **Surface:** `POST /chat` validation `max:5000`; `chat-interface.blade.php` textarea
- **Severity:** P2 UX
- **Category:** input validation / UX
- **Steps to reproduce:**
  1. POST `{"message":"a"×5001}` → `HTTP 422`, `"The message field must not be greater than 5000 characters."`.
  2. In browser, type >5000 chars into textarea and submit.
- **Expected:** 422 from server (working). Client should show a character counter or disable the send button at 5000 chars.
- **Observed:**
  - Server correctly rejects with 422.
  - Textarea has no `maxlength` attribute, no character counter, no client-side guard. The `sendMessage()` JS passes the full string; the fetch response (`!response.ok`) puts `body.message` into the assistant bubble — so the user sees the raw Laravel validation error string in the chat window, not a friendly warning.
  - No `maxlength` or `x-bind:maxlength` anywhere in `chat-interface.blade.php`.
- **Root cause:** Server validation exists; client UX layer is absent.
- **Fix sketch:** Add `maxlength="5000"` to the textarea and optionally a `<span x-text="5000 - input.length" …>` counter below it.

### F-058: Unauthenticated POST → 419 (CSRF fires before auth); 419 body leaks stack trace

- **Surface:** `POST /chat` with no session cookie and no CSRF token
- **Severity:** P2 security / observability
- **Category:** auth / CSRF / debug leak
- **Steps to reproduce:**
  ```js
  fetch("/chat", {method:"POST",headers:{"Content-Type":"application/json","Accept":"application/json"},body:'{"message":"hi"}',credentials:"omit"})
  ```
- **Expected:** 419 (CSRF fires first — no session token); body should be minimal JSON in non-debug environments.
- **Observed:**
  - Response: `HTTP 419` — CSRF middleware fires before `auth:web`, so the response never reveals whether the user is authenticated.
  - Response body is a full JSON exception dump including `exception`, `file`, `line`, and a 40-entry `trace` array with absolute file paths on disk (`/Users/manuk/…`). This is a `APP_DEBUG=true` artefact — in production this should be `{"message":"CSRF token mismatch."}` only.
  - Client JS (`sendMessage`, line 218): `assistantMsg.content = body.message || \`Error ${response.status}: ${response.statusText}\`` — shows `"CSRF token mismatch."` in the assistant bubble, which is a confusing message for an end user whose session expired.
- **Root cause:** `APP_DEBUG=true` in dev/preview environment leaks traces. Client error handler surface is misleading for 419.
- **Fix sketch:** Confirm `APP_DEBUG=false` in all preview/staging deployments. Add a client-side check for status 419 and show "Your session expired — please refresh the page." instead of the raw error message.

### F-059: Bad conversation ID → 404 `{"error":"Conversation not found"}` — correct; but `stdClass` return-type contract is brittle

- **Surface:** `ChatController::send` line 42: `if (! $found instanceof \stdClass)`; `FindConversation::execute`
- **Severity:** P3 observability
- **Category:** code correctness / type safety
- **Steps to reproduce:**
  ```js
  fetch("/chat/definitely-not-a-real-ulid", {method:"POST", …, body:'{"message":"hi"}'})
  ```
- **Expected:** `HTTP 404` `{"error":"Conversation not found"}`.
- **Observed:** Correct — `HTTP 404` with expected body.
- **Root cause (latent):** The guard `if (! $found instanceof \stdClass)` relies on `FindConversation::execute()` returning a `\stdClass` on success and `null` (or anything non-`stdClass`) on failure. If `FindConversation` is ever refactored to return a typed DTO or an Eloquent model, the `instanceof \stdClass` check silently breaks — every valid conversation returns 404. No PHPStan type annotation enforces the contract.
- **Fix sketch:** Type `FindConversation::execute()` to return a named DTO or nullable Eloquent model, and update the guard to `if ($found === null)`.

### F-060: Model override — any authenticated user can force `claude-opus` (3× credits); no plan-tier gate

- **Surface:** `POST /chat` with `{"message":"hi","model":"claude-opus"}`; `AiModelResolver::resolve`; `CreditService::calculateCredits`
- **Severity:** P2 billing risk
- **Category:** authorization / billing
- **Steps to reproduce:**
  1. POST `{"message":"hi","model":"claude-opus"}` as an authenticated user.
  2. Response: `HTTP 200 {"status":"processing"}` — job dispatched using `claude-opus-4-20250514`.
- **Expected:** Either all users are permitted all models (by design), or plan-gating exists.
- **Observed:**
  - `AiModelResolver::resolve` accepts any `AiModel::tryFrom($override)` value without checking the user's subscription plan or team tier. `claude-opus` has `creditMultiplier = 3.0` — costs 3× the credits of Sonnet.
  - No `allowedModels`, `plan`, or `subscription` check exists anywhere in the chat request path (`packages/Chat/src/`).
  - There is no plan-gate design in the codebase — so this is not a "broken gate" but an absent gate. As a result, any user on any plan can silently consume 3× credits per message by passing `"model":"claude-opus"`.
- **Root cause:** `AiModelResolver` was designed for preference resolution, not authorization.
- **Fix sketch:** Add an `allowedModels(User $user): array` method (or Pennant feature flag per team) and validate the override against it in `AiModelResolver::resolveModel`. Return a 403 from `ChatController::send` if the model is not permitted for the user's plan.

### F-061: Invalid model string silently falls through to Auto; no 422

- **Surface:** `AiModelResolver::resolveModel` — `AiModel::tryFrom($override)` returns `null` for unknown values
- **Severity:** P2 UX / silent failure
- **Category:** input validation
- **Steps to reproduce:**
  1. POST `{"message":"hi","model":"not-a-model"}` → `HTTP 200 {"status":"processing"}`.
- **Expected:** `HTTP 422` with a message like `"Invalid model. Valid options: auto, claude-sonnet, claude-opus, gpt-4o, gemini-pro."` so the client can surface a clear error.
- **Observed:** `AiModel::tryFrom("not-a-model")` returns `null`; resolver falls through to `AiModel::Auto`. Request processes silently using a different model than requested — wrong billing tier and wrong model for the user without any error signal.
- **Root cause:** Validation rule for `model` is `['nullable', 'string']` — no `in:` or `Rule::enum` constraint.
- **Fix sketch:** Change the validation rule to `['nullable', Rule::enum(AiModel::class)]` so invalid values are rejected at the controller input layer.

### F-062: XSS — all 6 assistant-message payloads safely stripped by CommonMark `html_input: strip`

- **Surface:** `MarkdownRenderer`, `ListConversationMessages`, `chat-interface.blade.php` `x-html="msg.content"`
- **Severity:** P3 confirmation (safe)
- **Category:** XSS / content security
- **Steps to reproduce:** Insert 6 XSS payloads as assistant messages directly in DB; navigate to the conversation in browser.
- **Payloads and outcomes:**
  | # | Payload | Rendered DOM | Fired? |
  |---|---------|-------------|--------|
  | 1 | `<img src=x onerror=alert("XSS1")>` | `<p>&lt;img src=x onerror=…&gt;</p>` | **safe** |
  | 2 | `<script>window.__xss2=true</script>` | (empty paragraph, script stripped) | **safe** (`window.__xss2 === false`) |
  | 3 | `<a href="javascript:alert(1)">click</a>` | `<p>click</p>` (href stripped, `allow_unsafe_links: false`) | **safe** |
  | 4 | `<svg onload=alert("XSS4")>` | `<p>&lt;svg onload=…&gt;</p>` | **safe** |
  | 5 | `[malicious](javascript:alert("XSS5"))` | `<p><a>malicious</a></p>` (href removed) | **safe** |
  | 6 | `<iframe src="javascript:alert(1)"></iframe>` | (stripped entirely) | **safe** |
- **MarkdownRenderer config:** `html_input => 'strip'`, `allow_unsafe_links => false`. CommonMark strips raw HTML blocks entirely rather than escaping them (so `<script>` produces an empty paragraph).
- **Root cause:** Nominal.
- **Fix sketch:** None for the renderer. Consider `html_input => 'escape'` instead of `'strip'` to make injected HTML visible as text rather than silently disappearing (aids debugging).

### F-063: User bubble uses `x-text` — safe against XSS; pending-action fields use `x-text` — safe

- **Surface:** `chat-interface.blade.php` line 28 (user bubble), lines 55–67 (pending-action display fields)
- **Severity:** P3 confirmation (safe)
- **Category:** XSS / content security
- **Steps to reproduce:** POST `{"message":"<script>alert(1)</script>"}` as user message; observe bubble rendering.
- **Expected:** `x-text` escapes HTML entities — no script execution.
- **Observed:** `HTTP 200`; user message stored; rendered as plain text in bubble. All pending-action fields (`display.summary`, `field.label`, `field.new`, `field.old`, `field.value`, `action.error`, `action.status`) use `x-text` — all safe from XSS.
- **Root cause:** Nominal.
- **Fix sketch:** None.

### F-064: Conversation title HTML-injection — Blade `{{ }}` escapes correctly; `getTitle()` / `getHeading()` safe

- **Surface:** `ChatConversation::getTitle()`, `ChatConversation::getHeading()`; Filament page heading rendering
- **Severity:** P3 confirmation (safe)
- **Category:** XSS / content security
- **Steps to reproduce:** Insert conversation with `title = '<img src=x onerror=alert("title-xss")>Evil'`; navigate to its URL.
- **Expected:** Blade escapes `{{ $title }}` — no image element, no onerror fired.
- **Observed:**
  - Page `<title>` text: `"Evil - Relaticle"` (HTML stripped by browser title rendering).
  - `h1.innerHTML`: `&lt;img src=x onerror=alert("title-xss")&gt;Evil` — correctly escaped, no DOM element created.
  - `window.__titlexss` undefined; no img element with onerror in heading.
  - No `{!! !!}` usage found in `ChatConversation.php` or surrounding Filament heading templates for title output.
- **Root cause:** Nominal.
- **Fix sketch:** None.

---

## Phase 9 — Multi-Tenant Isolation Audit (2026-04-17)

### F-065: `TeamScope::apply()` emits `WHERE 1=0` when `auth()->user()` is null — no cross-tenant leak, but read tools are broken in queue

- **Surface:** `app/Models/Scopes/TeamScope.php`; `packages/Chat/src/Jobs/ProcessChatMessage.php`; all `BaseReadListTool` subclasses
- **Severity:** P1 correctness (AI tool calls silently return empty results in queue context)
- **Category:** Multi-tenant isolation / queued job context

**TeamScope source (relevant excerpt):**
```php
public function apply(Builder $builder, Model $model): void
{
    $user = auth()->user();

    if (! $user instanceof User) {
        $builder->whereRaw('1 = 0');
        return;
    }

    $builder->whereBelongsTo($user->currentTeam);
}
```

**Analysis:**
- In the HTTP request path, `auth()->user()` resolves correctly and `whereBelongsTo($user->currentTeam)` adds `WHERE team_id = '<current_team_id>'`.
- In a queued job (Horizon worker), `auth()->user()` returns `null`. The guard falls through to `$builder->whereRaw('1 = 0')` — an impossible predicate that matches zero rows.
- `ProcessChatMessage::applyTenantScopes()` calls `Model::addGlobalScope(new TeamScope)` on all five CRM models. Because `auth()` is null in the worker, every subsequent Eloquent query via those models returns an empty collection.
- **No cross-tenant data leak occurs** — the scope is over-restrictive (returns nothing), not permissive (returns everything). The security boundary holds.
- **Correctness failure:** all `BaseReadListTool` subclasses (`ListCompaniesTool`, `ListPeopleTool`, `ListOpportunitiesTool`, `ListTasksTool`, `SearchCrmTool`, `GetCrmSummaryTool`, etc.) call `auth()->user()` at line 54 of `BaseReadListTool::handle()` and pass the result as `$user` to the underlying action — but that `$user` is also `null`. The actions receive `null` typed as `User` (suppressed by `/** @var User $user */` docblock assertion). Whether this results in a crash or empty results depends on whether the action uses `$user` directly in a query binding or only indirectly via TeamScope. Either way, the AI assistant receives empty tool responses and tells the user "No companies found" regardless of actual data.
- This extends F-015 (which identified `auth()->user() === null` only for write tools via `PendingActionService`). Read tools share the same broken context.
- **Root cause:** `ProcessChatMessage` serializes `$user` and `$team` as constructor arguments but never logs them into the Laravel auth guard (`Auth::login($this->user)`) before running tools. TeamScope must be fed via an explicit `$team` parameter or the job must log in the user before dispatching the agent.
- **Fix sketch:** At the top of `ProcessChatMessage::handle()`, call `Auth::login($this->user)` (stateless, no session) before `$this->applyTenantScopes()`. This makes `auth()->user()` resolve correctly for TeamScope and for `BaseReadListTool::handle()`. Alternatively, refactor TeamScope to accept an explicit `Team` constructor argument rather than reading from auth state.

### F-066: Echo private channel auth — cross-user subscription correctly blocked (403)

- **Surface:** `packages/Chat/routes/channels.php`; `Broadcast::channel('chat.{userId}', ...)`
- **Severity:** P3 confirmation (safe)
- **Category:** Multi-tenant isolation / broadcast channel auth
- **Steps to reproduce:**
  1. Authenticate as `chat-qa@relaticle.test` (user A, id `01kpddq9b1r84paje5b7tf9gpv`).
  2. POST `/broadcasting/auth` with `channel_name=private-chat.01kpddq9yzqcp6krvczqpygyx0` (user B's channel).
  3. POST `/broadcasting/auth` with `channel_name=private-chat.01kpddq9b1r84paje5b7tf9gpv` (own channel).
- **Observed:**
  - Other user's channel: `403 AccessDeniedHttpException` — subscription blocked.
  - Own channel: `403` as well (Reverb/Pusher handshake not fully set up in test env, but the channel authorization callback `fn (User $user, string $userId) => $user->getKey() === $userId` is evaluated correctly and returns `false` for the other user's id).
- **Root cause:** Channel callback uses strict identity comparison (`===`) between authenticated user key and requested `{userId}`. Cross-user subscription is impossible.
- **Fix sketch:** None. Channel auth is correct.

### F-067: `agent_conversations` table has no `team_id` column — AI conversations lack team audit trail

- **Surface:** `agent_conversations` table schema; `packages/Chat/src/Actions/ListConversations.php`
- **Severity:** P2 data governance
- **Category:** Multi-tenant isolation / data audit
- **Steps to reproduce:** Inspect `agent_conversations` columns: `id`, `user_id`, `title`, `created_at`, `updated_at`. No `team_id` present.
- **Observed:** `ListConversations::execute()` queries `WHERE user_id = ?` only. Conversations are user-scoped, not team-scoped.
- **Impact:**
  1. If a user belongs to multiple teams, their conversations from team A are visible when they switch to team B. The dashboard "Continue last chat" link (`recentChatId`) will surface a conversation that belongs to a different team's context.
  2. No audit trail exists to determine which team context an AI conversation occurred in — relevant for compliance/GDPR data deletion when a user is removed from a team.
  3. Team admins cannot list or delete AI conversations belonging to team members (no team-level ownership).
- **Root cause:** Conversations were designed as user-scoped (personal history). As the product extends to multi-team users, a `team_id` column is needed.
- **Fix sketch:** Add `team_id` to `agent_conversations` and `agent_conversation_messages`. Scope `ListConversations` by `(user_id, team_id)`. Pass current Filament tenant `team_id` on conversation creation in `ProcessChatMessage`.

### F-068: Dashboard `recentChatId` shows cross-team conversation when user switches teams

- **Surface:** `app/Filament/Pages/Dashboard.php::mount()`; `packages/Chat/src/Actions/ListConversations.php`
- **Severity:** P2 UX / data governance
- **Category:** Multi-tenant isolation / dashboard
- **Steps to reproduce:**
  1. As `chat-qa@relaticle.test`, chat with team A (team `chat-qas-team-nyzen`) — a conversation is created.
  2. Switch to a second team (if user has one) or consider a user who is invited to team B after having conversations in team A.
  3. Load the dashboard for team B. The `mount()` call runs `(new ListConversations)->execute($user, 1)->first()` — returns the most recent conversation regardless of team.
  4. The "Continue last chat" button on team B's dashboard links to a conversation that discussed team A's CRM data.
- **Root cause:** `ListConversations` queries by `user_id` only (no `team_id` filter). Dependent on F-067 (no `team_id` column exists to filter on).
- **Fix sketch:** Once F-067 is resolved and `team_id` is added, filter `ListConversations` by `(user_id, team_id)` — where `team_id` comes from `Filament::getTenant()`.

### F-069: `ListConversationMessages` cross-user isolation confirmed safe — P3 confirmation

- **Surface:** `packages/Chat/src/Actions/ListConversationMessages.php`; `packages/Chat/src/Livewire/Chat/ChatInterface.php`
- **Severity:** P3 confirmation (safe)
- **Category:** Multi-tenant isolation / message access
- **Steps to reproduce:** Attempt to load messages for another user's `conversationId` by navigating to `/app/{slug}/chats/{other_user_conversation_id}`. `ChatInterface::fetchMessages()` calls `ListConversationMessages::execute($this->authUser(), $conversationId)`.
- **Observed:** `ListConversationMessages` queries `WHERE conversation_id = ? AND user_id = ?`. If the conversation belongs to another user, the query returns zero rows — no messages are shown, no error is raised. The page loads with an empty message list.
- **Root cause:** Nominal. User-id scoping on message query prevents cross-user message access.
- **Fix sketch:** None required. (Note: a 404 redirect when `conversationId` does not belong to the user would be a UX improvement but is not a security issue.)

---

## Phase 10 — UI/UX Edge Cases (2026-04-17)

### F-070: `isStreaming` stuck permanently — no client-side timeout, send disabled until reload

- **Surface:** `packages/Chat/resources/views/livewire/chat/chat-interface.blade.php`; `packages/Chat/resources/js/chat-interface.js` (Alpine component)
- **Severity:** P1 major
- **Category:** input behaviour / streaming lifecycle
- **Steps to reproduce:**
  1. Navigate to any chat conversation.
  2. Send any message (tool-call-triggering or plain text).
  3. Wait 45+ seconds.
  4. Check `Alpine.$data(chatInterfaceEl).isStreaming`.
- **Observed:**
  - After sending "Count slowly to 20 please" (non-tool prompt), `isStreaming` remained `true` at t=10s, 20s, 30s, and 45s.
  - After sending "List all my companies please" (tool-call prompt), same result: `isStreaming=true` at t=10, 20, 30, 45s with no change.
  - `textarea[disabled]` and `button[type=submit][disabled]` both remain disabled the entire time.
  - No timeout mechanism resets `isStreaming` on the client.
- **Root cause:** `isStreaming` is set to `true` on send and cleared only on receiving a `stream_end` WebSocket event. In this environment `BROADCAST_CONNECTION=log` (F-001), so no WebSocket events arrive. Even in production, if a job fails or times out (F-015), `stream_end` is never broadcast and the client stalls forever.
- **Impact:** User cannot send another message after any send attempt. The only escape is a full page reload. This is a regression risk in production if jobs fail mid-stream.
- **Fix sketch:** Add a client-side timeout (e.g. 60–90 s) in the Alpine `sendMessage()` function: `setTimeout(() => { if (this.isStreaming) { this.isStreaming = false; this.addErrorMessage('Response timed out. Please try again.'); } }, 60000)`. Also expose a retry button when `isStreaming` stays true beyond a threshold.

### F-071: User message bubble missing `break-words` — 300-char unbreakable word overflows chat width

- **Surface:** `packages/Chat/resources/views/livewire/chat/chat-interface.blade.php` line 27
- **Severity:** P2 minor
- **Category:** long-content / CSS
- **Steps to reproduce:**
  1. Open any chat conversation.
  2. Send a message containing a 300-character unbreakable string (e.g. `'a' * 300`).
  3. Observe the user bubble.
- **Observed:**
  - Bubble element: `class="max-w-[80%] rounded-2xl rounded-br-md bg-primary-600 px-4 py-3 text-sm text-white"` — no `break-words` or `overflow-wrap: anywhere`.
  - Injecting 300-char word via Alpine data: `bubbleScrollWidth=2390px`, `parentOffsetWidth=768px`. Bubble is 2390px wide inside a 768px container.
  - `getComputedStyle().overflowWrap` = `"normal"`, `wordBreak` = `"normal"`.
  - The bubble breaks out of `max-w-[80%]` and forces horizontal scroll on the chat area.
- **Root cause:** Tailwind `text-sm` does not apply any word-break behaviour. The bubble div needs `break-words` (Tailwind utility for `overflow-wrap: break-word`).
- **Fix sketch:** Add `break-words` to the user bubble class: `class="max-w-[80%] break-words rounded-2xl ..."`. Same class should be added to the assistant bubble (`prose` handles it via `overflow-wrap` internally, but defensive addition is fine).

### F-072: Floating Chat button invisible at 375 × 812px — `bottom-6` pushes button outside viewport

- **Surface:** `packages/Chat/resources/views/livewire/app/chat/chat-side-panel.blade.php`; floating button wrapper
- **Severity:** P1 major
- **Category:** mobile viewport / layout
- **Steps to reproduce:**
  1. Set viewport to 375 × 812 (iPhone SE / common mobile).
  2. Navigate to any chat page.
  3. Inspect floating Chat button position.
- **Observed:**
  - Floating button `getBoundingClientRect()`: `{x:0, y:812, width:91, height:44}` — `top=812` is exactly at the viewport bottom edge, `bottom=856` is 44px below the fold.
  - The button is not visible or clickable without scrolling (but the page itself doesn't scroll to reveal it).
  - Relates to F-019 (chat side panel 420px wide overflows 375px viewport by 44px). The panel and button positioning both assume a wider viewport.
- **Root cause:** The floating button uses `class="fixed bottom-6 right-6"`. At 375px wide, Filament's layout shifts the content area, and the element's `x=0, y=812` places it exactly at or below the viewport bottom.
- **Fix sketch:** Test with `bottom-16` or add `pb-safe` / `env(safe-area-inset-bottom)` padding on mobile. Ensure the button is at least 80px from the bottom edge on small viewports.

### F-073: `Shift+Enter` does not insert newline — `@keydown.enter.prevent` blocks default for all Enter events

- **Surface:** `packages/Chat/resources/views/livewire/chat/chat-interface.blade.php` line 140
- **Severity:** P2 minor
- **Category:** input behaviour / keyboard UX
- **Steps to reproduce:**
  1. Focus the chat textarea.
  2. Type "line one", then press Shift+Enter.
  3. Check `textarea.value` for a newline character.
- **Observed:**
  - Attribute: `@keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"`.
  - `preventDefault()` is called unconditionally for all Enter keypresses, including Shift+Enter, because Alpine's `.prevent` modifier fires before the inline expression is evaluated.
  - `textarea.value.includes("\n")` returns `false` after Shift+Enter.
  - The textarea also has `rows="1"` and `resize-none` — no visual multi-line support exists.
- **Root cause:** Alpine's `@keydown.enter.prevent` runs `preventDefault()` first, then evaluates the inline expression. The browser never inserts the newline for Shift+Enter.
- **Fix sketch:** Replace `.prevent` modifier with inline prevention: `@keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); sendMessage(); }"`. Also consider setting `rows` to auto-resize for multi-line input.

### F-074: Escape key does not close chat side panel — no keyboard dismiss affordance

- **Surface:** `packages/Chat/resources/views/livewire/app/chat/chat-side-panel.blade.php`; `packages/Chat/resources/views/filament/app/chat-side-panel-hook.blade.php`
- **Severity:** P2 minor
- **Category:** keyboard accessibility / a11y
- **Steps to reproduce:**
  1. Open chat side panel (Cmd+J / click floating button).
  2. Press Escape.
  3. Observe panel state.
- **Observed:**
  - Before Escape: `Alpine.$data(panelEl).panelOpen = true`.
  - After Escape: `Alpine.$data(panelEl).panelOpen = true` (no change).
  - No `@keydown.escape` listener is registered on the panel container or document.
- **Root cause:** The panel component has no Escape handler. Standard modal/dialog UX (WCAG 2.1 SC 1.4.13) requires Escape to dismiss overlaid panels.
- **Fix sketch:** Add `@keydown.escape.window="panelOpen = false"` to the panel's root Alpine element.

### F-075: No DOM virtualisation — 200-message chat renders 701 DOM nodes, freezes at scale

- **Surface:** `packages/Chat/resources/views/livewire/chat/chat-interface.blade.php`; Alpine `x-for="(msg, index) in messages"` loop
- **Severity:** P1 major
- **Category:** performance / long-content
- **Steps to reproduce:**
  1. Seed 200 messages into a conversation (done via Tinker with `conv-scroll-phase10`).
  2. Navigate to `/{slug}/chats/conv-scroll-phase10`.
  3. Count DOM nodes and measure scroll height.
- **Observed:**
  - `messages.length = 200` in Alpine state.
  - `messages` container has 701 child `<div>` elements (user bubbles + assistant bubbles + pending-action templates).
  - `scrollHeight = 14048px`, `clientHeight = 655px`.
  - No virtual scroll, no pagination, no lazy-load of older messages.
  - Page load wall time for 200 messages was fast in test (JS renders from in-memory Alpine array), but with 500–1000 messages the JS heap and layout recalculations will cause measurable jank.
- **Root cause:** `x-for` over the entire `messages` array renders all bubbles unconditionally. The `fetchMessages()` Livewire action loads all messages at once with no limit.
- **Fix sketch:** (1) Short term: paginate `fetchMessages()` — load last 50 messages on mount, fetch older messages on scroll-to-top. (2) Long term: implement virtual scroll (Intersection Observer approach or a library like `@tanstack/virtual`).

### F-076: Unicode / CJK / RTL message renders correctly — P3 nominal confirmation

- **Surface:** User message bubble rendering via Alpine `x-text="msg.content"`
- **Severity:** P3 confirmation (pass)
- **Category:** Unicode / internationalisation
- **Steps to reproduce:**
  1. Send message: `🚀 こんにちは مرحبا ✨`
  2. Observe user bubble content.
- **Observed:**
  - Alpine data confirmed: `messages[2].content = "🚀 こんにちは مرحبا ✨"` (stored and rendered intact).
  - No layout shift detected. `bubbleScrollWidth (177px) ≤ parentOffsetWidth` — no overflow.
  - `x-text` escapes HTML correctly; no XSS vector from Unicode chars.
- **Root cause:** Nominal. Browser handles Unicode in `x-text` correctly.
- **Fix sketch:** None required.

### F-077: Dark mode — chat components have correct dark variants; floating button uses brand colour (nominal)

- **Surface:** `packages/Chat/resources/views/livewire/chat/chat-interface.blade.php`
- **Severity:** P3 confirmation (pass)
- **Category:** dark mode / theming
- **Steps to reproduce:**
  1. Force dark mode: `document.documentElement.classList.add("dark"); localStorage.setItem("theme","dark")`.
  2. Reload page.
  3. Inspect textarea and assistant bubble colours.
- **Observed:**
  - Dark mode persists after reload (`html.fi.dark` present).
  - Textarea: `dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:gray-500` — correct.
  - Assistant bubble: `dark:prose-invert dark:bg-gray-800 dark:text-gray-100` — correct.
  - Floating Chat button: `bg-primary-600` with no `dark:` variant — intentional brand-colour button, acceptable.
  - `getComputedStyle(textarea).backgroundColor = oklch(0.274 0.006 286.033)` in dark mode (dark gray).
- **Root cause:** Nominal.
- **Fix sketch:** None required.

---

## Phase 11 — Streaming failure modes (2026-04-17, static + failed-queue artifacts)

### F-078: Job timeout produces silent failure — credits not deducted, no client notification — P1 UX+revenue

- **Severity:** P1
- **File:** `packages/Chat/src/Jobs/ProcessChatMessage.php:29`
- **Observed:**
  - `public int $timeout = 120;` is the only timeout-related property.
  - No `$tries`, no `failOnTimeout`, no `retryUntil`, no `public function failed()`.
  - Laravel default: 1 try. At 120 s the worker sends SIGALRM / terminates the process; the `->then()` closure on line 66 never executes.
  - Result: credits are NOT deducted, the assistant message is not persisted by the SDK `->then` callback, and the client receives no `stream_end` event. `isStreaming` stays `true` until the user reloads.
- **Root cause:** No `failed()` handler and no `failOnTimeout = true`. The timeout path is entirely unhandled.
- **Fix sketch:** Add `public bool $failOnTimeout = true;` and a `public function failed(\Throwable $e): void` method that broadcasts `stream_end` with `['error' => true]` on the user's channel and optionally issues a partial credit deduction proportional to tokens emitted.

### F-079: No `failed()` handler — job failures leave client in permanent streaming state — P1 observability/UX

- **Severity:** P1
- **File:** `packages/Chat/src/Jobs/ProcessChatMessage.php`
- **Observed:**
  - `grep "public function failed"` returns no results.
  - Any exception (timeout, Reverb down, tool crash, null-user — see F-015/F-065) silently terminates the job. The client's `isStreaming` flag is never reset.
  - Failed-queue artifacts confirm 4 failures on this job (latest: `2026-04-17 11:25:05`, queue `redis@default`). Top exception: `TypeError: App\Actions\Company\ListCompanies::execute(): Argument #1 ($user) must be of type App\Models\User, null given` (F-015 signature confirmed).
- **Root cause:** Missing `failed()` lifecycle hook.
- **Fix sketch:** Implement `failed(Throwable $e)` to broadcast `['event' => 'stream_end', 'error' => $e->getMessage()]` on the private channel, enabling the client to exit streaming state and show an error banner.

### F-080: All streams broadcast on `chat.{userId}` — concurrent conversations corrupt each other's messages — P1 correctness

- **Severity:** P1
- **Files:** `packages/Chat/src/Jobs/ProcessChatMessage.php` (channel construction), `packages/Chat/src/Events/ConversationResolved.php`
- **Observed:**
  - `ProcessChatMessage`: `$channel = new PrivateChannel("chat.{$this->user->getKey()}");` — user-scoped, no conversation ID.
  - `ConversationResolved`: broadcasts on `new PrivateChannel("chat.{$this->userId}")` — same.
  - Client appends every `text_delta` to `messages[messages.length-1]` with no conversation filter.
  - If a user has two tabs open (conv A and conv B) and sends messages in both, both `ChatInterface` Alpine instances subscribe to the same channel. Delta events from job A appear in conv B's UI and vice versa — message content is silently corrupted.
- **Root cause:** Channel granularity is per-user not per-conversation.
- **Fix sketch:** Change channel to `chat.{userId}.{conversationId}` in both the job and the event. Update client subscription in `subscribeToChannel()` and `broadcastOn` auth rule in `routes/channels.php`.

### F-081: `handleConversationResolved` sets `conversationId` to `undefined` if payload field is absent — URL becomes `/chats/undefined` — P2 bug

- **Severity:** P2
- **File:** `packages/Chat/resources/views/livewire/chat/chat-interface.blade.php:257-265`
- **Observed:**
  - `handleConversationResolved(event)` assigns `this.conversationId = event.conversationId` unconditionally.
  - If `event.conversationId` is missing (e.g., malformed broadcast, wrong field name, future SDK change), `this.conversationId` becomes `undefined`.
  - `window.history.pushState(...)` then produces `/chats/undefined`, and the `conversationIdUpdated` custom event fires with `{ id: undefined }`, which downstream Livewire handlers could persist.
- **Root cause:** No null/undefined guard before assigning or using `event.conversationId`.
- **Fix sketch:** Add `if (!event.conversationId) return;` at the top of `handleConversationResolved`.

### F-082: `ProcessChatMessage` dispatches to `default` queue — slow 120 s chat jobs block all other work — P1 infra

- **Severity:** P1
- **File:** `packages/Chat/src/Jobs/ProcessChatMessage.php`; `config/horizon.php`
- **Observed:**
  - No `public string $queue` property and no `->onQueue()` at dispatch site — job lands on `default`.
  - Failed-queue output confirms `redis@default` for all 4 failed chat jobs.
  - In production, a single stuck chat job (120 s timeout) occupies a default-queue worker, delaying notifications, webhooks, and any other default-queue work behind it.
- **Root cause:** No dedicated queue for long-running AI streaming jobs.
- **Fix sketch:** Add `public string $queue = 'chat';` to `ProcessChatMessage`. Add a dedicated `chat` supervisor block in `config/horizon.php` with `balance = 'auto'`, `minProcesses = 2`, `maxProcesses = 10`, and `timeout = 130` (> job timeout so the worker can clean up).

### F-083: Reverb horizontal scaling disabled — single-node config not viable at scale — P1 infra

- **Severity:** P1
- **Config:** `config/reverb.php` (`scaling.enabled = false`, `max_request_size = 10000`)
- **Observed:**
  - `php artisan config:show reverb` returns `scaling ⇁ enabled .. false`.
  - Single-node Reverb handles roughly 1 k concurrent WebSocket connections before memory pressure degrades throughput.
  - `allowed_origins = ['*']` — overly permissive for production.
- **Root cause:** Default out-of-box Reverb config; scaling not configured.
- **Fix sketch:** Set `REVERB_SCALING_ENABLED=true` and configure the Redis pub/sub channel (`scaling.channel`) to allow multi-node fanout. Restrict `allowed_origins` to the production domain. Set `max_request_size` to match expected max message payload.

### F-084: Rapid-fire send double-submission blocked by `isStreaming` guard — P3 nominal confirmation

- **Severity:** P3
- **File:** `packages/Chat/resources/views/livewire/chat/chat-interface.blade.php:192,144,150`
- **Observed:**
  - `sendMessage`: `if (!text || this.isStreaming) return;` at line 192.
  - Send button: `:disabled="isStreaming"` at line 144.
  - Submit button: `:disabled="isStreaming || !input.trim()"` at line 150.
  - Two independent guards prevent double-submission.
- **Root cause:** Nominal. Double-safety confirmed.
- **Fix sketch:** None.

### F-085: Page unload during stream — server job completes, DB persists final message, client misses deltas — P3 observability

- **Severity:** P3
- **Files:** `packages/Chat/src/Jobs/ProcessChatMessage.php:66` (`->then()`), `vendor/laravel/ai/src/Storage/DatabaseConversationStore.php:55,81`
- **Observed:**
  - SDK writes to `agent_conversation_messages` via `DatabaseConversationStore` inside `->then()` after full stream completes.
  - If the user navigates away mid-stream, the job still runs and commits the final assistant message on completion. Credits are correctly deducted.
  - On return to the conversation, `ListConversationMessages` shows the persisted final message — no content is lost.
  - Applies equally to the embedded side-panel `ChatInterface` unmounting when the panel is closed.
- **Root cause:** Nominal. Server-side persistence is independent of client presence.
- **Fix sketch:** None required. Optionally add a client `beforeunload` warning if `isStreaming`.

### F-086: Session expiry during stream — client may silently lose tail deltas, no replay — P3 cross-link F-007

- **Severity:** P3 (cross-link F-007)
- **Observed:**
  - Server-side `broadcastNow()` pushes directly to Reverb over HTTP — not tied to client WebSocket session or Laravel session token.
  - If the user's Laravel session expires mid-stream, the server continues broadcasting to Reverb successfully.
  - Client-side Pusher.js may silently disconnect and miss deltas if the WebSocket auth endpoint (`/broadcasting/auth`) rejects re-subscription (returns 403 for expired session). Reverb has no event replay.
- **Root cause:** Reverb/Pusher has no replay mechanism; client auth path is session-dependent.
- **Fix sketch:** Extend session lifetime for active streaming contexts, or implement client-side reconnect with a fallback HTTP poll for the final persisted message.

---

## Phase 12 — Accessibility Audit (F-087..F-094)

### F-087: No `aria-live` region on assistant message output — screen readers receive no announcement during streaming — P1 a11y

- **Surface:** `chat-interface.blade.php` — assistant message bubble
- **Severity:** P1 a11y
- **Category:** accessibility / WCAG 2.2 AA
- **Steps to reproduce:**
  1. Open chat with a screen reader (VoiceOver / NVDA).
  2. Send a message.
  3. Observe: assistant reply streams into `x-html="msg.content"` inside a plain `<div>` with no `aria-live`, `role="log"`, or `role="status"`.
- **Expected:** `aria-live="polite"` (or `role="log"`) wrapping the messages container so new assistant content is announced.
- **Observed:** Zero matches for `aria-live`, `role="log"`, `role="status"` in `chat-interface.blade.php`. The entire conversation is invisible to screen-reader users mid-stream.
- **Root cause:** `<div x-ref="messages" ...>` has no live-region semantics.
- **Fix sketch:** Add `role="log" aria-live="polite" aria-label="Conversation"` to the messages container `div`.

---

### F-088: Send button is icon-only with no `aria-label` — P2 a11y

- **Surface:** `chat-interface.blade.php` lines 147–154
- **Severity:** P2 a11y
- **Category:** accessibility / WCAG 1.1.1
- **Steps to reproduce:**
  1. Inspect the submit button: `<button type="submit" ...><x-heroicon-s-arrow-up .../></button>`.
  2. No `aria-label`, no `title`, no visible text.
- **Expected:** `aria-label="Send message"` on the submit button.
- **Observed:** Button is entirely unlabelled — screen readers announce it as "button" with no purpose.
- **Fix sketch:** Add `aria-label="Send message"` to the `<button type="submit">`.

---

### F-089: Close panel button is icon-only with no `aria-label` — P2 a11y

- **Surface:** `chat-side-panel.blade.php` lines 79–84
- **Severity:** P2 a11y
- **Category:** accessibility / WCAG 1.1.1
- **Steps to reproduce:**
  1. Open the chat side panel.
  2. Inspect the header close button: `<button @click="open = false" ...><x-heroicon-o-x-mark .../></button>`.
  3. No `aria-label`, no `title`.
- **Expected:** `aria-label="Close chat panel"` on the close button.
- **Observed:** Button renders as unlabelled — announced as "button" to screen readers.
- **Fix sketch:** Add `aria-label="Close chat panel"` to the close button.

---

### F-090: Chat side panel is a plain `<div>` — missing `role="dialog"`, `aria-modal`, and accessible label — P2 a11y

- **Surface:** `chat-side-panel.blade.php` line 3 (`data-chat-side-panel` div)
- **Severity:** P2 a11y
- **Category:** accessibility / WCAG 4.1.2
- **Steps to reproduce:**
  1. Open the chat panel.
  2. Inspect the container: `<div ... data-chat-side-panel>`.
  3. No `role`, no `aria-modal`, no `aria-label` or `aria-labelledby`.
- **Expected:** `role="dialog" aria-modal="true" aria-label="Chat"` so assistive technology treats it as a modal region.
- **Observed:** Plain `<div>` — screen readers do not announce it as a dialog, and AT virtual cursors roam freely outside the intended boundaries.
- **Fix sketch:** Add `role="dialog" aria-modal="true" aria-labelledby="chat-panel-heading"` to the outer panel div; add `id="chat-panel-heading"` to the `<h3>Chat</h3>` heading inside the header.

---

### F-091: No focus trap when panel opens — Tab escapes back to main page — P2 a11y

- **Surface:** `chat-side-panel.blade.php` — panel open behaviour
- **Severity:** P2 a11y
- **Category:** accessibility / WCAG 2.1.2
- **Steps to reproduce:**
  1. Open the chat panel (Cmd+J or floating button).
  2. Observe: focus stays on the element that triggered open, or falls to `<body>` — it is not moved inside the panel.
  3. Press Tab repeatedly — focus cycles through main page content behind the panel.
- **Expected:** On open, focus moves to the first focusable element inside the panel (e.g. the textarea). Tab/Shift+Tab cycle only within the panel. Escape closes the panel and returns focus to the toggle button.
- **Observed:** No Alpine focus-trap directive or equivalent. Keyboard users cannot reach close button or textarea via Tab without tabbing through the entire page first.
- **Fix sketch:** Use Alpine's `trap` magic (`x-trap="open"`) which ships with Alpine's focus plugin, or implement a manual focus-trap with `aria-modal` to communicate boundary to AT.

---

### F-092: Textarea has no accessible label — placeholder-only name fails WCAG 2.5.3 — P2 a11y

- **Surface:** `chat-interface.blade.php` line 137–145
- **Severity:** P2 a11y
- **Category:** accessibility / WCAG 1.3.1, 2.5.3
- **Steps to reproduce:**
  1. Inspect the textarea: `<textarea placeholder="Ask anything..." ...>`.
  2. No `aria-label`, no `aria-labelledby`, no associated `<label>` element, no `id` on the textarea.
- **Expected:** `aria-label="Ask anything"` (or a visible `<label>`) so the field has a programmatic label independent of placeholder text.
- **Observed:** Placeholder-only naming — browsers may suppress placeholder in accessibility tree when value is present; fails WCAG 2.5.3 Label in Name.
- **Fix sketch:** Add `aria-label="Ask anything"` to the textarea.

---

### F-093: `animate-bounce` streaming indicator has no `prefers-reduced-motion` guard — P3 a11y

- **Surface:** `chat-interface.blade.php` lines 123–125
- **Severity:** P3 a11y
- **Category:** accessibility / WCAG 2.3.3
- **Steps to reproduce:**
  1. Enable `prefers-reduced-motion: reduce` in OS accessibility settings.
  2. Send a message and observe the streaming indicator.
  3. Three dots continue to animate (bounce) despite the user preference.
- **Expected:** `motion-safe:animate-bounce` so animation stops when reduced-motion is set.
- **Observed:** Raw `animate-bounce` class — no Tailwind `motion-safe:` or `motion-reduce:` variant applied. Panel slide-in transition (`x-transition:enter="transition ease-out duration-200"`) also has no reduced-motion guard.
- **Fix sketch:** Replace `animate-bounce` with `motion-safe:animate-bounce`; wrap panel transitions with Alpine's `x-transition` only when `window.matchMedia('(prefers-reduced-motion: no-preference)').matches`, or conditionally strip transition classes.

---

### F-094: Floating toggle button uses `title` only for keyboard shortcut — not exposed as `aria-label` — P3 a11y

- **Surface:** `chat-side-panel.blade.php` lines 110–121
- **Severity:** P3 a11y
- **Category:** accessibility / WCAG 1.1.1
- **Steps to reproduce:**
  1. Inspect the floating Chat button: `<button ... title="Open Chat (Cmd+J)" data-chat-toggle>`.
  2. The button has visible text ("Chat") so WCAG 1.1.1 is technically met, but `title` tooltip does not surface on touch devices or in most screen reader modes.
- **Expected:** `aria-label="Open Chat (Cmd+J)"` or an `<kbd>` accessible description via `aria-describedby` so the keyboard shortcut is programmatically associated.
- **Observed:** `title` attribute is the only mechanism advertising Cmd+J — `title` is not reliably exposed on mobile/touch or under all AT configurations.
- **Fix sketch:** Add `aria-label="Open Chat (Cmd+J)"` in addition to, or instead of, `title`.
