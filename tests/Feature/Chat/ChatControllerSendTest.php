<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Relaticle\Chat\Http\Controllers\ChatController;
use Relaticle\Chat\Jobs\ProcessChatMessage;
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

it('uses the client-supplied conversation_id when one is provided and the conversation does not exist', function (): void {
    Queue::fake();

    $clientId = '019dded5-aaaa-7bbb-8ccc-444400000000';

    $response = $this->postJson(route('chat.send'), [
        'message' => 'hi',
        'conversation_id' => $clientId,
    ]);

    $response->assertOk()
        ->assertJsonPath('conversation_id', $clientId);

    expect(DB::table('agent_conversations')->where('id', $clientId)->exists())->toBeTrue();
    expect(DB::table('agent_conversations')->where('id', $clientId)->value('team_id'))
        ->toBe($this->team->getKey());

    Queue::assertPushed(ProcessChatMessage::class);
});

it('rejects a client-supplied conversation_id that already belongs to another user', function (): void {
    $owner = User::factory()->withPersonalTeam()->create();
    $sharedId = '019dded5-bbbb-7bbb-8ccc-555500000000';

    DB::table('agent_conversations')->insert([
        'id' => $sharedId,
        'user_id' => $owner->getKey(),
        'team_id' => $owner->currentTeam->getKey(),
        'title' => 'private',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->postJson(route('chat.send'), [
        'message' => 'hi',
        'conversation_id' => $sharedId,
    ]);

    $response->assertStatus(403);
});

it('rejects malformed conversation_id', function (): void {
    $response = $this->postJson(route('chat.send'), [
        'message' => 'hi',
        'conversation_id' => 'not-a-uuid',
    ]);

    $response->assertStatus(422);
});

it('falls back to server-generated id when none provided', function (): void {
    Queue::fake();

    $response = $this->postJson(route('chat.send'), ['message' => 'hi']);

    $response->assertOk();
    $id = $response->json('conversation_id');
    expect($id)->toBeString()->and(strlen($id))->toBe(36);
});
