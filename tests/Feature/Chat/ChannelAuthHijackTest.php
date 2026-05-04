<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

function chatChannelAuthCheck(User $user, string $conversationId): bool
{
    $broadcaster = app(BroadcasterContract::class);
    $reflection = new ReflectionClass($broadcaster);
    $prop = $reflection->getProperty('channels');
    $prop->setAccessible(true);
    $channels = $prop->getValue($broadcaster);

    $callback = $channels['chat.conversation.{conversationId}'] ?? null;

    if ($callback === null) {
        require __DIR__.'/../../../packages/Chat/routes/channels.php';

        return chatChannelAuthCheck($user, $conversationId);
    }

    return (bool) $callback($user, $conversationId);
}

it('rejects channel auth for a conversation id that does not exist', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $conversationId = (string) Str::uuid();

    expect(chatChannelAuthCheck($user, $conversationId))->toBeFalse();
    expect(DB::table('agent_conversations')->where('id', $conversationId)->exists())->toBeFalse();
});

it('blocks user B from pre-claiming a conversation id minted by user A', function (): void {
    $userA = User::factory()->withPersonalTeam()->create();
    $userB = User::factory()->withPersonalTeam()->create();
    $conversationId = (string) Str::uuid();

    expect(chatChannelAuthCheck($userB, $conversationId))->toBeFalse();
    expect(DB::table('agent_conversations')->where('id', $conversationId)->exists())->toBeFalse();

    actingAs($userA);
    $init = post('/chat/conversations', ['conversation_id' => $conversationId])->assertOk();
    expect($init->json('conversation_id'))->toBe($conversationId);

    expect(chatChannelAuthCheck($userB, $conversationId))->toBeFalse();
    expect(chatChannelAuthCheck($userA, $conversationId))->toBeTrue();
});
