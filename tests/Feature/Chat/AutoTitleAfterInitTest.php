<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Relaticle\Chat\Http\Controllers\ChatController;
use Relaticle\Chat\Models\AgentConversation;
use Relaticle\Chat\Models\AiCreditBalance;

mutates(ChatController::class);

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);

    AiCreditBalance::query()->create([
        'team_id' => $this->team->getKey(),
        'credits_remaining' => 100,
        'credits_used' => 0,
        'period_starts_at' => now()->startOfMonth(),
        'period_ends_at' => now()->endOfMonth(),
    ]);
});

it('updates the title from the first message when conversation was pre-minted by /chat/init', function (): void {
    Queue::fake();

    $conversationId = (string) Str::uuid7();

    $this->postJson(route('chat.conversations.init'), ['conversation_id' => $conversationId])
        ->assertOk();

    expect(AgentConversation::query()->find($conversationId)?->title)->toBe('');

    $this->postJson(route('chat.send'), [
        'conversation_id' => $conversationId,
        'message' => 'Show me my recent companies please',
        'mentions' => [],
    ])->assertOk();

    expect(AgentConversation::query()->find($conversationId)?->title)
        ->toBe('Show me my recent companies please');
});

it('does not overwrite a non-empty title on subsequent sends', function (): void {
    Queue::fake();

    $conversationId = (string) Str::uuid7();

    $this->postJson(route('chat.conversations.init'), ['conversation_id' => $conversationId])
        ->assertOk();

    $this->postJson(route('chat.send'), [
        'conversation_id' => $conversationId,
        'message' => 'first message',
        'mentions' => [],
    ])->assertOk();

    AgentConversation::query()->where('id', $conversationId)->update(['title' => 'manual rename']);

    $this->postJson(route('chat.send'), [
        'conversation_id' => $conversationId,
        'message' => 'a second message',
        'mentions' => [],
    ])->assertOk();

    expect(AgentConversation::query()->find($conversationId)?->title)->toBe('manual rename');
});
