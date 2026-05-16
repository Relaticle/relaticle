<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Relaticle\Chat\Http\Controllers\ChatController;
use Relaticle\Chat\Models\AiCreditBalance;
use Tests\Helpers\ChatDocument;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

mutates(ChatController::class);

it('marks a conversation as cancelled when cancel endpoint hit', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    $conversationId = (string) Str::uuid7();

    DB::table('agent_conversations')->insert([
        'id' => $conversationId,
        'user_id' => (string) $user->getKey(),
        'team_id' => $team->getKey(),
        'title' => 'Test conversation',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cacheKey = "chat:cancel:{$conversationId}";

    expect(Cache::has($cacheKey))->toBeFalse();

    actingAs($user);
    postJson(route('chat.cancel', ['conversationId' => $conversationId]))
        ->assertOk()
        ->assertJson(['cancelled' => true]);

    expect(Cache::get($cacheKey))->toBe((string) $user->getKey());
});

it('returns 404 when another user tries to cancel a conversation', function (): void {
    Queue::fake();

    $userA = User::factory()->withPersonalTeam()->create();
    $userB = User::factory()->withPersonalTeam()->create();
    $teamA = $userA->currentTeam;

    AiCreditBalance::updateOrCreate(
        ['team_id' => $teamA->getKey()],
        ['credits_remaining' => 100, 'period_ends_at' => now()->addMonth()],
    );

    actingAs($userA);
    $response = postJson(route('chat.conversations.create'), [
        'document' => ChatDocument::fromText('hi'),
    ])->assertOk();

    $conversationId = $response->json('conversation_id');

    actingAs($userB);
    postJson(route('chat.cancel', ['conversationId' => $conversationId]))
        ->assertNotFound();

    expect(Cache::has("chat:cancel:{$conversationId}"))->toBeFalse();
});

it('returns 401 for unauthenticated cancel', function (): void {
    postJson(route('chat.cancel', ['conversationId' => 'x']))
        ->assertUnauthorized();
});
