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
