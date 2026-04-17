# Chat TDD Backlog — 2026-04-17

Generated from 100 QA findings (F-001..F-100). Only P0/P1/P2 findings are included.
Each entry is a self-contained spec a TDD agent can pick up without re-reading the original finding.
Run order follows `Recommended fix order` in the Summary section.

---

## tests/Feature/Chat/ChatControllerTest.php

### F-049: Non-atomic credit gate allows concurrent over-spend

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Atomic `UPDATE ai_credit_balances SET credits_remaining = credits_remaining - 1 WHERE team_id = ? AND credits_remaining > 0` returning affected rows before dispatching the job. Refund inside job on failure.
- **Pest test (red first):**
  ```php
  it('concurrent sends with 1 credit only dispatch one job', function () {
      $user = User::factory()->withPersonalTeam()->create();
      $team = $user->currentTeam;
      AiCreditBalance::factory()->for($team)->create(['credits_remaining' => 1]);

      Bus::fake();
      actingAs($user);

      // Simulate two "simultaneous" sends
      $responses = collect(range(1, 2))->map(fn () =>
          $this->postJson('/chat', ['message' => 'hello'])
      );

      $statuses = $responses->map(fn ($r) => $r->getStatusCode());
      // Exactly one 200, one 402 — must FAIL today (both return 200)
      expect($statuses->filter(fn ($s) => $s === 200)->count())->toBe(1);
      expect($statuses->filter(fn ($s) => $s === 402)->count())->toBe(1);
      Bus::assertDispatchedTimes(ProcessChatMessage::class, 1);
  });
  ```
- **Manual verification:** DB `credits_remaining` should be 0 after a single successful dispatch.

---

### F-050: `resetPeriod` has no caller — credits never reset

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Create `php artisan chat:reset-credits` command; schedule `->monthlyOn(1, '00:00')` in `bootstrap/app.php`.
- **Pest test (red first):**
  ```php
  it('chat:reset-credits command resets all team balances to their plan allowance', function () {
      $team = Team::factory()->create();
      AiCreditBalance::factory()->for($team)->create(['credits_remaining' => 0, 'credits_used' => 500]);

      // Command must exist and run successfully — will FAIL today (command does not exist)
      $this->artisan('chat:reset-credits')->assertExitCode(0);

      expect($team->fresh()->aiCreditBalance->credits_remaining)->toBeGreaterThan(0);
      expect($team->fresh()->aiCreditBalance->credits_used)->toBe(0);
  });
  ```

---

### F-048: 402 credit exhaustion rendered as plain chat bubble — no upgrade CTA

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Detect `body.error === 'credits_exhausted'` in Alpine `sendMessage` handler and render a dedicated paywall card with "Upgrade plan" CTA instead of inserting raw error text into an assistant bubble.
- **Pest test (red first):**
  ```php
  it('credits_exhausted response includes machine-readable error key', function () {
      $user = User::factory()->withPersonalTeam()->create();
      $team = $user->currentTeam;
      AiCreditBalance::factory()->for($team)->create(['credits_remaining' => 0]);

      $response = actingAs($user)->postJson('/chat', ['message' => 'hello']);

      $response->assertStatus(402)
          ->assertJsonFragment(['error' => 'credits_exhausted']);
      // Currently fails if JSON key is absent or uses a different key name
  });
  ```

---

### F-060: Model override — any user can force claude-opus without plan gate

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Add `allowedModels(User $user): array` check in `AiModelResolver::resolve`; return 403 when requested model is not in the allowed set for the user's plan.
- **Pest test (red first):**
  ```php
  it('free-tier user cannot force claude-opus model', function () {
      $user = User::factory()->withPersonalTeam()->create(); // free tier
      AiCreditBalance::factory()->for($user->currentTeam)->create(['credits_remaining' => 100]);

      $response = actingAs($user)->postJson('/chat', [
          'message' => 'hello',
          'model'   => 'claude-opus',
      ]);

      // Must FAIL today — currently returns 200 and dispatches claude-opus job
      $response->assertStatus(403);
  });
  ```

---

### F-061: Invalid model string silently falls to Auto — no 422

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Change validation rule for `model` to `['nullable', Rule::enum(AiModel::class)]`.
- **Pest test (red first):**
  ```php
  it('sends 422 for invalid model string', function () {
      $user = User::factory()->withPersonalTeam()->create();
      AiCreditBalance::factory()->for($user->currentTeam)->create(['credits_remaining' => 100]);

      $response = actingAs($user)->postJson('/chat', [
          'message' => 'hello',
          'model'   => 'not-a-real-model',
      ]);

      // Must FAIL today — currently returns 200 and silently substitutes Auto
      $response->assertStatus(422)
          ->assertJsonValidationErrors(['model']);
  });
  ```

---

## tests/Feature/Chat/PendingActionControllerTest.php

### F-026: `reject()` has no DB-level lock — concurrent rejects can double-resolve

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Wrap `reject()` in `DB::transaction()` with `lockForUpdate` re-fetch, mirroring `approve()`.
- **Pest test (red first):**
  ```php
  it('concurrent reject requests only resolve the action once', function () {
      $user = User::factory()->withPersonalTeam()->create();
      $action = PendingAction::factory()->for($user)->create(['status' => 'pending']);

      actingAs($user);

      // Two simultaneous requests
      $r1 = $this->postJson("/chat/actions/{$action->id}/reject");
      $r2 = $this->postJson("/chat/actions/{$action->id}/reject");

      $statuses = [$r1->status(), $r2->status()];
      sort($statuses);

      // One 200, one 422 — will FAIL today if both return 200
      expect($statuses)->toBe([200, 422]);
      expect($action->fresh()->resolved_at)->not->toBeNull();
  });
  ```

---

### F-023: Expired action status not flipped on approve attempt

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Inside `validateResolvable()` 422 branch for expired actions, also set `status = 'expired'` on the record.
- **Pest test (red first):**
  ```php
  it('approving an expired action transitions its status to expired', function () {
      $user = User::factory()->withPersonalTeam()->create();
      $action = PendingAction::factory()->for($user)->create([
          'status'     => 'pending',
          'expires_at' => now()->subMinute(),
      ]);

      actingAs($user)->postJson("/chat/actions/{$action->id}/approve")
          ->assertStatus(422);

      // Must FAIL today — status remains 'pending' after the 422 attempt
      expect($action->fresh()->status)->toBe('expired');
  });
  ```

---

## tests/Feature/Chat/CreditServiceTest.php

### F-051: `deduct()` silently no-ops when balance row absent — invisible billing gap

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Replace the silent `return` with a `Log::critical` + exception throw when `$balance` is not an `AiCreditBalance` instance.
- **Pest test (red first):**
  ```php
  it('deduct throws when no credit balance row exists', function () {
      $team = Team::factory()->create();
      // Intentionally do NOT create an AiCreditBalance row

      expect(fn () => (new CreditService)->deduct($team, 1, 'claude-sonnet-4-6', 0, 0, 0))
          ->toThrow(\RuntimeException::class);
      // Must FAIL today — currently silently returns without throwing
  });
  ```

---

## tests/Feature/Chat/MentionsTest.php

### F-042: `q` wildcard `%` enables full-team data enumeration

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Escape `%` and `_` before interpolating into the `ilike` clause.
- **Pest test (red first):**
  ```php
  it('percent sign in q is treated as a literal character not a wildcard', function () {
      $user = User::factory()->withPersonalTeam()->create();
      Company::factory()->for($user->currentTeam)->count(5)->create(['name' => 'Acme Inc']);

      $response = actingAs($user)->getJson('/chat/mentions?q=%25%25'); // q=%%

      // If escaping is absent, response returns 5 results because %% matches all
      // After fix it should return 0 (no company names contain literal "%")
      $response->assertOk()
          ->assertJsonCount(0, 'data');
      // Must FAIL today — returns up to 15 records
  });
  ```

---

### F-043: No rate limit on `/chat/mentions`

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Add `->middleware('throttle:60,1')` to the `chat.mentions` route.
- **Pest test (red first):**
  ```php
  it('mentions endpoint returns 429 after 60 requests per minute', function () {
      $user = User::factory()->withPersonalTeam()->create();
      actingAs($user);

      collect(range(1, 60))->each(fn () =>
          $this->getJson('/chat/mentions?q=test')->assertOk()
      );

      // 61st request should be rate-limited — FAILS today (returns 200)
      $this->getJson('/chat/mentions?q=test')->assertStatus(429);
  });
  ```

---

## tests/Feature/Chat/TenantIsolationTest.php

### F-065: TeamScope emits `WHERE 1=0` in queued job — AI read tools return empty

- **Severity:** P1
- **Blocks:** F-015 (same root cause — must fix together)
- **Fix sketch:** Call `Auth::login($this->user)` (stateless) at the top of `ProcessChatMessage::handle()` before `applyTenantScopes()`.
- **Pest test (red first):**
  ```php
  it('ProcessChatMessage resolves team companies for the correct team in queue context', function () {
      $user = User::factory()->withPersonalTeam()->create();
      $team = $user->currentTeam;
      Company::factory()->for($team)->count(3)->create();
      $conversation = AgentConversation::factory()->for($user)->create();

      // Dispatch the job directly (not via HTTP) to simulate queue worker context
      $job = new ProcessChatMessage($user, $team, $conversation->id, 'List my companies');
      app()->call([$job, 'handle']);

      // The job should have persisted an assistant message containing company data
      $messages = AgentConversationMessage::where('conversation_id', $conversation->id)
          ->where('role', 'assistant')
          ->get();

      // Must FAIL today — messages is empty because TeamScope returns 0 rows
      expect($messages)->not->toBeEmpty();
  });
  ```

---

### F-015: Write tools crash with `TypeError` — `auth()->user()` is null in queue

- **Severity:** P0
- **Blocks:** must be fixed alongside F-065 (same root fix)
- **Fix sketch:** `Auth::login($this->user)` in `ProcessChatMessage::handle()` before agent streaming.
- **Pest test (red first):**
  ```php
  it('ProcessChatMessage does not land in failed_jobs when AI proposes a company create', function () {
      Queue::fake();
      $user = User::factory()->withPersonalTeam()->create();
      $team = $user->currentTeam;
      AiCreditBalance::factory()->for($team)->create(['credits_remaining' => 100]);
      $conversation = AgentConversation::factory()->for($user)->create();

      $job = new ProcessChatMessage($user, $team, $conversation->id, 'Create company Acme Corp');

      // If auth()->user() is null the job will throw — FAILS today via TypeError
      expect(fn () => app()->call([$job, 'handle']))->not->toThrow(\TypeError::class);
  });
  ```

---

### F-067: `agent_conversations` has no `team_id` — cross-team data leaks to wrong dashboard

- **Severity:** P2
- **Blocks:** F-068 depends on this migration
- **Fix sketch:** Add `team_id` FK column to `agent_conversations`; filter `ListConversations` by `(user_id, team_id)`.
- **Pest test (red first):**
  ```php
  it('ListConversations filters by current team not just user', function () {
      $user = User::factory()->withPersonalTeam()->create();
      $teamA = $user->currentTeam;
      $teamB = Team::factory()->create();
      $user->teams()->attach($teamB);

      $convA = AgentConversation::factory()->for($user)->create(['team_id' => $teamA->id]);
      $convB = AgentConversation::factory()->for($user)->create(['team_id' => $teamB->id]);

      $results = (new ListConversations)->execute($user, $teamA, 10);

      // Must FAIL today — no team_id column means both conversations are returned
      expect($results->pluck('id'))->toContain($convA->id)
          ->not->toContain($convB->id);
  });
  ```

---

### F-068: Dashboard `recentChatId` surfaces cross-team conversation

- **Severity:** P2
- **Blocks:** depends on F-067 (team_id migration must exist first)
- **Fix sketch:** Pass current team to `ListConversations` in `Dashboard::mount()`.
- **Pest test (red first):**
  ```php
  it('dashboard recentChatId only shows conversations from the current team', function () {
      $user = User::factory()->withPersonalTeam()->create();
      $teamA = $user->currentTeam;
      $teamB = Team::factory()->create();
      $user->teams()->attach($teamB);

      $convA = AgentConversation::factory()->for($user)->create(['team_id' => $teamA->id]);
      $convB = AgentConversation::factory()->for($user)->create([
          'team_id'    => $teamB->id,
          'updated_at' => now()->addMinute(), // newer, so it would win without team filter
      ]);

      actingAs($user);
      livewire(\App\Filament\Pages\Dashboard::class, ['tenant' => $teamA])
          ->assertSet('recentChatId', $convA->id);
      // Must FAIL today — returns $convB->id because query is user-only
  });
  ```

---

## tests/Feature/Chat/StreamingTest.php

### F-080: All streams broadcast on `chat.{userId}` — concurrent conversations corrupt each other

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Change channel to `chat.{userId}.{conversationId}` in `ProcessChatMessage` and `ConversationResolved`; update client subscription and channel auth rule.
- **Pest test (red first):**
  ```php
  it('streaming events include conversation_id to scope client filtering', function () {
      Event::fake();
      $user = User::factory()->withPersonalTeam()->create();
      $team = $user->currentTeam;
      AiCreditBalance::factory()->for($team)->create(['credits_remaining' => 100]);
      $conv = AgentConversation::factory()->for($user)->create();

      actingAs($user)->postJson('/chat', [
          'message'         => 'hello',
          'conversationId'  => $conv->id,
      ])->assertOk();

      // Stream events should broadcast on a conversation-scoped channel
      // Currently broadcasts on user-only channel — FAILS
      Event::assertBroadcast(fn ($event) =>
          str_contains($event->broadcastOn()[0]->name, $conv->id)
      );
  });
  ```

---

### F-082: `ProcessChatMessage` dispatches to `default` queue — blocks all other work

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Add `public string $queue = 'chat';` to `ProcessChatMessage`.
- **Pest test (red first):**
  ```php
  it('ProcessChatMessage is dispatched to the chat queue', function () {
      Bus::fake();
      $user = User::factory()->withPersonalTeam()->create();
      AiCreditBalance::factory()->for($user->currentTeam)->create(['credits_remaining' => 100]);

      actingAs($user)->postJson('/chat', ['message' => 'hello'])->assertOk();

      Bus::assertDispatched(ProcessChatMessage::class, function ($job) {
          // Must FAIL today — job queue is 'default' not 'chat'
          return $job->queue === 'chat';
      });
  });
  ```

---

### F-078: Job timeout produces silent failure — no `failed()` handler, credits not deducted, client stuck

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Add `public bool $failOnTimeout = true;` and `public function failed(\Throwable $e): void` that broadcasts `stream_end` with `['error' => true]`.
- **Pest test (red first):**
  ```php
  it('ProcessChatMessage::failed broadcasts stream_end error event', function () {
      Event::fake();
      $user = User::factory()->withPersonalTeam()->create();
      $conv = AgentConversation::factory()->for($user)->create();

      $job = new ProcessChatMessage($user, $user->currentTeam, $conv->id, 'test');
      $job->failed(new \RuntimeException('timeout'));

      // Must FAIL today — failed() method does not exist, no broadcast occurs
      Event::assertBroadcast(fn ($event) =>
          isset($event->payload()['error']) && $event->payload()['error'] === true
      );
  });
  ```

---

## tests/Feature/Chat/ConversationListingTest.php

### F-095 / F-038: `extractPendingActions` N+1 — one DB query per pending_action per message

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Collect all `pending_action_id` values from all messages first; batch-fetch with a single `whereIn`; hydrate from in-memory map.
- **Pest test (red first):**
  ```php
  it('ListConversationMessages issues at most 2 queries regardless of pending action count', function () {
      $user = User::factory()->withPersonalTeam()->create();
      $conv = AgentConversation::factory()->for($user)->create();

      // Seed 20 assistant messages each with a pending_action_id in tool_results
      $pendingActions = PendingAction::factory()->count(20)->for($user)->create(['status' => 'pending']);
      collect($pendingActions)->each(fn ($pa, $i) =>
          AgentConversationMessage::factory()->create([
              'conversation_id' => $conv->id,
              'role'            => 'assistant',
              'tool_results'    => json_encode([['pending_action_id' => $pa->id]]),
          ])
      );

      $queryCount = 0;
      DB::listen(fn () => $queryCount++);

      (new ListConversationMessages)->execute($user, $conv->id);

      // Must FAIL today — issues 1 + 20 = 21 queries
      expect($queryCount)->toBeLessThanOrEqual(2);
  });
  ```

---

### F-033: No conversation index — users with >10 chats cannot reach older ones

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Create a Filament page at `/{slug}/chats` (index) listing all user conversations paginated; add "View all" link at bottom of sidebar group.
- **Pest test (red first):**
  ```php
  it('chat index page is accessible and lists all conversations', function () {
      $user = User::factory()->withPersonalTeam()->create();
      AgentConversation::factory()->for($user)->count(15)->create();

      actingAs($user);
      $response = get(route('filament.app.chats.index', ['tenant' => $user->currentTeam->slug]));

      $response->assertOk();
      // Must FAIL today — only 10 sidebar items; no separate index page exists
      expect($response->getContent())->toContain('15'); // pagination count or all 15 titles
  });
  ```

---

### F-036: No delete conversation UI — backend ready, frontend absent

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Add trash icon button to each sidebar conversation item wired to `DeleteConversation` action.
- **Pest test (red first):**
  ```php
  it('deleting a conversation via Livewire removes it from the sidebar', function () {
      $user = User::factory()->withPersonalTeam()->create();
      $conv = AgentConversation::factory()->for($user)->create(['title' => 'Target Conv']);

      actingAs($user);
      livewire(\Relaticle\Chat\Livewire\App\Chat\ChatSidebarNav::class)
          ->call('deleteConversation', $conv->id)
          ->assertDontSee('Target Conv');

      // Must FAIL today — deleteConversation method does not exist on the component
      expect(AgentConversation::find($conv->id))->toBeNull();
  });
  ```

---

### F-037: `DeleteConversation` leaves orphaned `pending_actions` rows

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Add `DB::table('pending_actions')->where('conversation_id', $conversationId)->delete();` inside the transaction in `DeleteConversation`.
- **Pest test (red first):**
  ```php
  it('deleting a conversation also deletes its associated pending_actions', function () {
      $user = User::factory()->withPersonalTeam()->create();
      $conv = AgentConversation::factory()->for($user)->create();
      $pa = PendingAction::factory()->create([
          'conversation_id' => $conv->id,
          'user_id'         => $user->id,
          'team_id'         => $user->currentTeam->id,
      ]);

      (new DeleteConversation)->execute($user, $conv->id);

      // Must FAIL today — pending_actions row remains after conversation deletion
      expect(PendingAction::find($pa->id))->toBeNull();
  });
  ```

---

## tests/Feature/Chat/MarkdownRendererTest.php

### F-071: User message bubble overflows on 300-char unbreakable word

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Add `break-words` Tailwind class to user bubble `<div>` in `chat-interface.blade.php`.
- **Pest test (red first):**
  ```php
  it('chat interface blade contains break-words class on user bubble', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/chat/chat-interface.blade.php')
      );

      // The user message bubble div must carry break-words
      // Must FAIL today — class is absent
      expect($blade)->toContain('break-words');
  });
  ```

---

## tests/Feature/Chat/SidebarTest.php

### F-034 / F-096: `wire:poll.60s` fires unconditionally — AJAX flood at scale

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Remove `wire:poll.60s` from `chat-sidebar-nav.blade.php`; rely on `x-on:chat:conversation-created.window` event already present.
- **Pest test (red first):**
  ```php
  it('chat sidebar nav blade does not contain unconditional wire:poll', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/app/chat/chat-sidebar-nav.blade.php')
      );

      // Must FAIL today — file contains `wire:poll.60s`
      expect($blade)->not->toContain('wire:poll');
  });
  ```

---

### F-010: Sidebar shows no empty-state copy when conversation list is empty

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Add conditional placeholder `<li>` with copy "No conversations yet" when conversation list is empty.
- **Pest test (red first):**
  ```php
  it('chat sidebar shows empty state copy when user has no conversations', function () {
      $user = User::factory()->withPersonalTeam()->create();
      actingAs($user);

      livewire(\Relaticle\Chat\Livewire\App\Chat\ChatSidebarNav::class)
          ->assertSee('No conversations yet');
      // Must FAIL today — no such string exists in the component output
  });
  ```

---

## tests/Feature/Chat/ChatPageTest.php

### F-012: URL querystring not cleaned after `conversation.resolved`

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Strip querystring before computing URL rewrite path in `handleConversationResolved`; fix regex to handle `/chats?message=...` case.
- **Pest test (red first):**
  ```php
  it('handleConversationResolved regex replaces /chats?message= correctly', function () {
      // Pure JS logic test — validate the regex via a PHP equivalent
      $input    = '/my-team/chats?message=Say+hi';
      $newId    = 'new-conv-id';
      $pattern  = '#/chats(/[^?]*)?$#'; // expected fixed pattern
      $result   = preg_replace($pattern, '/chats/' . $newId, $input);

      // Must FAIL if the current broken regex is used instead
      expect($result)->toBe('/my-team/chats/' . $newId);
  });
  ```

---

### F-073: Shift+Enter does not insert newline — `.prevent` fires before conditional

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Replace `@keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"` with `@keydown.enter="if(!$event.shiftKey){$event.preventDefault();sendMessage()}"`.
- **Pest test (red first):**
  ```php
  it('chat-interface blade does not use enter.prevent modifier that blocks shift+enter', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/chat/chat-interface.blade.php')
      );

      // Must FAIL today — file contains @keydown.enter.prevent
      expect($blade)->not->toContain('@keydown.enter.prevent');
  });
  ```

---

### F-081: `handleConversationResolved` sets `conversationId` to `undefined` on missing payload field

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Add `if (!event.conversationId) return;` guard at the top of `handleConversationResolved`.
- **Pest test (red first):**
  ```php
  it('chat-interface blade guards against undefined conversationId in handleConversationResolved', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/chat/chat-interface.blade.php')
      );

      // Must FAIL today — no null/undefined guard exists
      expect($blade)->toContain('if (!event.conversationId)');
  });
  ```

---

### F-057: No client-side character counter — 5001-char message shows raw validation error in bubble

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Add `maxlength="5000"` attribute to the textarea; optionally add a visible character counter.
- **Pest test (red first):**
  ```php
  it('chat textarea has maxlength attribute set to 5000', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/chat/chat-interface.blade.php')
      );

      // Must FAIL today — no maxlength attribute on the textarea
      expect($blade)->toContain('maxlength="5000"');
  });
  ```

---

## tests/Feature/Chat/SidePanelTest.php

### F-017: Side-panel event listeners accumulate on each Livewire re-init — Cmd+J fires N times

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Add Alpine `destroy()` hook that calls `removeEventListener` for each handler stored in `_keydownHandler`, `_chatSendHandler`, `_navigatedHandler`.
- **Pest test (red first):**
  ```php
  it('chat-side-panel blade defines a destroy() method to clean up window listeners', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/app/chat/chat-side-panel.blade.php')
      );

      // Must FAIL today — no destroy() function defined
      expect($blade)->toContain('destroy()');
      expect($blade)->toContain('removeEventListener');
  });
  ```

---

### F-019: Side panel overflows 375px mobile viewport by 45px

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Add `max-w-full` or `:style="{ width: Math.min(width, window.innerWidth) + 'px' }"` binding; clamp `minWidth` to `Math.min(360, window.innerWidth)`.
- **Pest test (red first):**
  ```php
  it('chat-side-panel blade constrains width to viewport width on mobile', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/app/chat/chat-side-panel.blade.php')
      );

      // Must FAIL today — no viewport width constraint in style binding
      expect($blade)->toMatch('/Math\.min.*window\.innerWidth/');
  });
  ```

---

### F-020: Suggested prompts in side panel are a dead drop — `chat:send-message` has no listener

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Add `#[On('chat:send-message')]` to `ChatInterface::sendFromPanel()` or wire via Alpine browser event.
- **Pest test (red first):**
  ```php
  it('ChatSidePanel handleSendFromDashboard wiring reaches ChatInterface sendMessage', function () {
      $user = User::factory()->withPersonalTeam()->create();
      $conv = AgentConversation::factory()->for($user)->create();
      Bus::fake();

      actingAs($user);
      livewire(\Relaticle\Chat\Livewire\App\Chat\ChatSidePanel::class)
          ->call('handleSendFromDashboard', 'CRM overview', 'suggestion');

      // Must FAIL today — event is dispatched but never received; no job dispatched
      Bus::assertDispatched(ProcessChatMessage::class);
  });
  ```

---

### F-021: Two ChatInterface instances on `/chats/{id}` both subscribe to same Echo channel

- **Severity:** P1
- **Blocks:** related to F-016, F-080
- **Fix sketch:** Add `window.__chatEchoAttached` guard in `setupEchoListener()` so only one subscription is created per page.
- **Pest test (red first):**
  ```php
  it('chat-interface blade includes a guard against duplicate Echo subscriptions', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/chat/chat-interface.blade.php')
      );

      // Must FAIL today — no __chatEchoAttached or equivalent guard exists
      expect($blade)->toContain('__chatEchoAttached');
  });
  ```

---

### F-074: Escape key does not close chat side panel

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Add `@keydown.escape.window="panelOpen = false"` to the panel's root Alpine element.
- **Pest test (red first):**
  ```php
  it('chat-side-panel blade has escape key handler to close the panel', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/app/chat/chat-side-panel.blade.php')
      );

      // Must FAIL today — no escape key handler exists
      expect($blade)->toMatch('/@keydown\.escape/');
  });
  ```

---

## tests/Browser/Chat/A11yTest.php

### F-087: No `aria-live` region on assistant output — screen readers silent

- **Severity:** P1
- **Blocks:** nothing upstream
- **Fix sketch:** Add `role="log" aria-live="polite" aria-label="Conversation"` to the messages container `<div x-ref="messages">`.
- **Pest test (red first):**
  ```php
  it('messages container has aria-live attribute', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/chat/chat-interface.blade.php')
      );

      // Must FAIL today — no aria-live present
      expect($blade)->toContain('aria-live');
  });
  ```

---

### F-088: Send button is icon-only with no `aria-label`

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Add `aria-label="Send message"` to the `<button type="submit">`.
- **Pest test (red first):**
  ```php
  it('submit button has aria-label', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/chat/chat-interface.blade.php')
      );

      // Must FAIL today — button has no aria-label
      expect($blade)->toContain('aria-label="Send message"');
  });
  ```

---

### F-089: Close panel button is icon-only with no `aria-label`

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Add `aria-label="Close chat panel"` to the close button.
- **Pest test (red first):**
  ```php
  it('close panel button has aria-label', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/app/chat/chat-side-panel.blade.php')
      );

      // Must FAIL today — close button has no aria-label
      expect($blade)->toContain('aria-label="Close chat panel"');
  });
  ```

---

### F-090: Chat side panel is a plain `<div>` — missing `role="dialog"` and `aria-modal`

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Add `role="dialog" aria-modal="true" aria-labelledby="chat-panel-heading"` to the outer panel div.
- **Pest test (red first):**
  ```php
  it('chat side panel container has dialog role and aria-modal', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/app/chat/chat-side-panel.blade.php')
      );

      // Must FAIL today — no role="dialog" exists
      expect($blade)->toContain('role="dialog"');
      expect($blade)->toContain('aria-modal="true"');
  });
  ```

---

### F-091: No focus trap when panel opens — Tab escapes to main page

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Add `x-trap="panelOpen"` (Alpine focus plugin) to the panel container.
- **Pest test (red first):**
  ```php
  it('chat side panel uses Alpine focus trap', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/app/chat/chat-side-panel.blade.php')
      );

      // Must FAIL today — no x-trap directive
      expect($blade)->toContain('x-trap');
  });
  ```

---

### F-092: Textarea has no accessible label — placeholder-only naming

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Add `aria-label="Ask anything"` to the textarea element.
- **Pest test (red first):**
  ```php
  it('chat textarea has an accessible aria-label', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/chat/chat-interface.blade.php')
      );

      // Must FAIL today — no aria-label on textarea
      expect($blade)->toContain('aria-label=');
  });
  ```

---

## tests/Browser/Chat/StreamingUiTest.php

### F-070: `isStreaming` stuck permanently — no timeout resets UI

- **Severity:** P1
- **Blocks:** F-078 / F-079 should be fixed together (failed() handler needed too)
- **Fix sketch:** Add `setTimeout(() => { if (this.isStreaming) { this.isStreaming = false; this.addErrorMessage('Response timed out. Please try again.'); } }, 60000)` in `sendMessage()`.
- **Pest test (red first):**
  ```php
  it('chat-interface blade includes a streaming timeout in sendMessage', function () {
      $blade = file_get_contents(
          base_path('packages/Chat/resources/views/livewire/chat/chat-interface.blade.php')
      );

      // Must FAIL today — no setTimeout / timeout mechanism for isStreaming
      expect($blade)->toMatch('/setTimeout.*isStreaming/s');
  });
  ```

---

## tests/Feature/Chat/MigrationTest.php

### F-002: `agent_conversations` table missing — `laravel/ai` not installed

- **Severity:** P1
- **Blocks:** F-005, F-015, F-065, F-067 all depend on this table existing
- **Fix sketch:** `composer require laravel/ai:^0.4.3` and `php artisan migrate`.
- **Pest test (red first):**
  ```php
  it('agent_conversations table exists', function () {
      // Must FAIL if laravel/ai is absent and migration has not run
      expect(\Illuminate\Support\Facades\Schema::hasTable('agent_conversations'))->toBeTrue();
  });

  it('agent_conversation_messages table exists', function () {
      expect(\Illuminate\Support\Facades\Schema::hasTable('agent_conversation_messages'))->toBeTrue();
  });
  ```

---

## tests/Feature/Chat/DashboardTest.php

### F-005: Dashboard 500 — `agent_conversations` relation does not exist

- **Severity:** P0
- **Blocks:** depends on F-002 (table must exist)
- **Fix sketch:** Install `laravel/ai`, run `php artisan migrate`.
- **Pest test (red first):**
  ```php
  it('dashboard renders without 500 for an authenticated user', function () {
      $user = User::factory()->withPersonalTeam()->create();
      actingAs($user);

      get(route('filament.app.pages.dashboard', ['tenant' => $user->currentTeam->slug]))
          ->assertOk();
      // Must FAIL today with HTTP 500 if agent_conversations table is missing
  });
  ```

---

### F-008: Greeting uses server UTC — wrong for non-UTC users

- **Severity:** P2
- **Blocks:** nothing upstream
- **Fix sketch:** Store `timezone` on users; use `now($user->timezone ?? 'UTC')` in `Dashboard::getGreeting()`.
- **Pest test (red first):**
  ```php
  it('greeting reflects user local timezone not server UTC', function () {
      $this->travelTo(now('UTC')->setHour(10)); // 10:00 UTC = 14:00 UTC+4
      $user = User::factory()->withPersonalTeam()->create(['timezone' => 'Asia/Baku']);
      actingAs($user);

      livewire(\App\Filament\Pages\Dashboard::class)
          ->assertSeeHtml('Good afternoon');
      // Must FAIL today — greeting uses server UTC and shows "Good morning"
  });
  ```
