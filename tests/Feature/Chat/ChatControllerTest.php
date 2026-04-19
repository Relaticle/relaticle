<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Relaticle\Chat\Http\Controllers\ChatController;
use Relaticle\Chat\Jobs\ProcessChatMessage;
use Relaticle\Chat\Models\AiCreditBalance;

mutates(ChatController::class);

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);
    Filament::setTenant($this->team);

    AiCreditBalance::query()->create([
        'team_id' => $this->team->getKey(),
        'credits_remaining' => 100,
        'credits_used' => 0,
        'period_starts_at' => now()->startOfMonth(),
        'period_ends_at' => now()->endOfMonth(),
    ]);
});

it('rejects unauthenticated requests', function (): void {
    auth()->logout();

    $this->postJson(route('chat.send'), ['message' => 'hello'])
        ->assertUnauthorized();
});

it('returns 402 when credits are exhausted', function (): void {
    AiCreditBalance::query()
        ->where('team_id', $this->team->getKey())
        ->update(['credits_remaining' => 0]);

    $this->postJson(route('chat.send'), ['message' => 'hello'])
        ->assertStatus(402)
        ->assertJsonPath('error', 'credits_exhausted');
});

it('validates message is required', function (): void {
    $this->postJson(route('chat.send'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

it('validates message max length', function (): void {
    $this->postJson(route('chat.send'), ['message' => str_repeat('a', 5001)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

it('dispatches a chat job when credits are available', function (): void {
    Queue::fake();

    $response = $this->postJson(route('chat.send'), ['message' => 'hello']);

    $response->assertOk();
    $response->assertJson(['status' => 'processing']);
    Queue::assertPushed(ProcessChatMessage::class);
});

it('continues an existing conversation', function (): void {
    Queue::fake();

    DB::table('agent_conversations')->insert([
        'id' => 'conv-existing',
        'user_id' => $this->user->getKey(),
        'team_id' => $this->team->getKey(),
        'title' => 'Existing conversation',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->postJson(route('chat.send', ['conversation' => 'conv-existing']), [
        'message' => 'continue please',
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'processing']);
    Queue::assertPushed(ProcessChatMessage::class);
});

it('lists conversations for current user', function (): void {
    DB::table('agent_conversations')->insert([
        'id' => 'conv-1',
        'user_id' => $this->user->getKey(),
        'team_id' => $this->team->getKey(),
        'title' => 'Test conversation',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->getJson(route('chat.conversations'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Test conversation');
});

it('does not list conversations of other users', function (): void {
    $otherUser = User::factory()->withPersonalTeam()->create();

    DB::table('agent_conversations')->insert([
        'id' => 'conv-other',
        'user_id' => $otherUser->getKey(),
        'team_id' => $otherUser->currentTeam->getKey(),
        'title' => 'Not mine',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->getJson(route('chat.conversations'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('deletes own conversation', function (): void {
    DB::table('agent_conversations')->insert([
        'id' => 'conv-to-delete',
        'user_id' => $this->user->getKey(),
        'team_id' => $this->team->getKey(),
        'title' => 'Delete me',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->deleteJson(route('chat.conversations.destroy', 'conv-to-delete'))
        ->assertOk();

    $this->assertDatabaseMissing('agent_conversations', ['id' => 'conv-to-delete']);
});

it('cannot delete another user conversation', function (): void {
    $otherUser = User::factory()->withPersonalTeam()->create();

    DB::table('agent_conversations')->insert([
        'id' => 'conv-other',
        'user_id' => $otherUser->getKey(),
        'team_id' => $otherUser->currentTeam->getKey(),
        'title' => 'Not yours',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->deleteJson(route('chat.conversations.destroy', 'conv-other'))
        ->assertNotFound();
});

it('rejects unknown model overrides with 422', function (): void {
    Queue::fake();

    $this->postJson(route('chat.send'), [
        'message' => 'hello',
        'model' => 'not-a-real-model',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('model');

    Queue::assertNotPushed(ProcessChatMessage::class);
});

it('accepts known model override values', function (): void {
    Queue::fake();

    $this->postJson(route('chat.send'), [
        'message' => 'hello',
        'model' => 'claude-sonnet',
    ])->assertOk();

    Queue::assertPushed(ProcessChatMessage::class);
});

it('returns the conversation id so the client can subscribe', function (): void {
    Queue::fake();

    $response = $this->postJson(route('chat.send'), ['message' => 'hello']);

    $response->assertOk()
        ->assertJsonStructure(['status', 'conversation_id']);

    expect($response->json('conversation_id'))->toBeString();
});

it('atomically reserves a credit so concurrent sends cannot overspend', function (): void {
    AiCreditBalance::query()
        ->where('team_id', $this->team->getKey())
        ->update(['credits_remaining' => 1, 'credits_used' => 99]);

    Queue::fake();

    $first = $this->postJson(route('chat.send'), ['message' => 'first']);
    $second = $this->postJson(route('chat.send'), ['message' => 'second']);

    $first->assertOk();
    $second->assertStatus(402);

    expect(AiCreditBalance::query()->where('team_id', $this->team->getKey())->value('credits_remaining'))->toBe(0);
});

it('does not reserve a credit when the request fails validation', function (): void {
    $this->postJson(route('chat.send'), ['message' => ''])
        ->assertUnprocessable();

    expect(AiCreditBalance::query()->where('team_id', $this->team->getKey())->value('credits_remaining'))->toBe(100);
});

it('cleans up messages when deleting a conversation', function (): void {
    DB::table('agent_conversations')->insert([
        'id' => 'conv-cleanup',
        'user_id' => $this->user->getKey(),
        'team_id' => $this->team->getKey(),
        'title' => 'Cleanup test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('agent_conversation_messages')->insert([
        'id' => 'msg-1',
        'conversation_id' => 'conv-cleanup',
        'user_id' => $this->user->getKey(),
        'agent' => CrmAssistant::class,
        'role' => 'user',
        'content' => 'Hello',
        'attachments' => '[]',
        'tool_calls' => '[]',
        'tool_results' => '[]',
        'usage' => '{}',
        'meta' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->deleteJson(route('chat.conversations.destroy', 'conv-cleanup'))
        ->assertOk();

    $this->assertDatabaseMissing('agent_conversation_messages', ['conversation_id' => 'conv-cleanup']);
});
