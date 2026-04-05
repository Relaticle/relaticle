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
        'title' => 'Not yours',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->deleteJson(route('chat.conversations.destroy', 'conv-other'))
        ->assertNotFound();
});

it('cleans up messages when deleting a conversation', function (): void {
    DB::table('agent_conversations')->insert([
        'id' => 'conv-cleanup',
        'user_id' => $this->user->getKey(),
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
