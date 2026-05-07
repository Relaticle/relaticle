<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Relaticle\Chat\Models\AiCreditBalance;
use Tests\Helpers\ChatDocument;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

it('rejects channel auth for a conversation id that does not exist', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $conversationId = (string) Str::uuid();

    expect(chatChannelAuth($user, $conversationId))->toBeFalse();
    expect(DB::table('agent_conversations')->where('id', $conversationId)->exists())->toBeFalse();
});

it('blocks user B from accessing a conversation created by user A', function (): void {
    Queue::fake();

    $userA = User::factory()->withPersonalTeam()->create();
    $userB = User::factory()->withPersonalTeam()->create();

    AiCreditBalance::query()->create([
        'team_id' => $userA->currentTeam->getKey(),
        'credits_remaining' => 100,
        'credits_used' => 0,
        'period_starts_at' => now()->startOfMonth(),
        'period_ends_at' => now()->endOfMonth(),
    ]);

    actingAs($userA);
    $response = postJson(route('chat.conversations.create'), [
        'document' => ChatDocument::fromText('hello'),
    ])->assertOk();

    $conversationId = $response->json('conversation_id');
    expect($conversationId)->toBeString();

    expect(chatChannelAuth($userB, $conversationId))->toBeFalse();
    expect(chatChannelAuth($userA, $conversationId))->toBeTrue();
});
