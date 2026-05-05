<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

it('rejects channel auth for a conversation id that does not exist', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $conversationId = (string) Str::uuid();

    expect(chatChannelAuth($user, $conversationId))->toBeFalse();
    expect(DB::table('agent_conversations')->where('id', $conversationId)->exists())->toBeFalse();
});

it('blocks user B from pre-claiming a conversation id minted by user A', function (): void {
    $userA = User::factory()->withPersonalTeam()->create();
    $userB = User::factory()->withPersonalTeam()->create();
    $conversationId = (string) Str::uuid();

    expect(chatChannelAuth($userB, $conversationId))->toBeFalse();
    expect(DB::table('agent_conversations')->where('id', $conversationId)->exists())->toBeFalse();

    actingAs($userA);
    $init = post('/chat/conversations', ['conversation_id' => $conversationId])->assertOk();
    expect($init->json('conversation_id'))->toBe($conversationId);

    expect(chatChannelAuth($userB, $conversationId))->toBeFalse();
    expect(chatChannelAuth($userA, $conversationId))->toBeTrue();
});
