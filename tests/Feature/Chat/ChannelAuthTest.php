<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Illuminate\Support\Facades\DB;

function chatChannelAuth(User $user, string $conversationId): bool
{
    $broadcaster = app(BroadcasterContract::class);
    $reflection = new ReflectionClass($broadcaster);
    $prop = $reflection->getProperty('channels');
    $prop->setAccessible(true);
    $channels = $prop->getValue($broadcaster);

    $callback = $channels['chat.conversation.{conversationId}'] ?? null;

    if ($callback === null) {
        // The conversation channel is registered on boot; forcing the package
        // provider to reboot re-registers it in rare worker-restart scenarios.
        require __DIR__.'/../../../packages/Chat/routes/channels.php';

        return chatChannelAuth($user, $conversationId);
    }

    return (bool) $callback($user, $conversationId);
}

it('grants access to own conversation channel', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    DB::table('agent_conversations')->insert([
        'id' => 'conv-mine',
        'user_id' => $user->getKey(),
        'team_id' => $user->current_team_id,
        'title' => 'Mine',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(chatChannelAuth($user, 'conv-mine'))->toBeTrue();
});

it('denies access to another user conversation channel', function (): void {
    $mine = User::factory()->withPersonalTeam()->create();
    $other = User::factory()->withPersonalTeam()->create();

    DB::table('agent_conversations')->insert([
        'id' => 'conv-other',
        'user_id' => $other->getKey(),
        'team_id' => $other->current_team_id,
        'title' => 'Other',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(chatChannelAuth($mine, 'conv-other'))->toBeFalse();
});

it('denies access to nonexistent conversation channel', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    expect(chatChannelAuth($user, 'does-not-exist'))->toBeFalse();
});

it('denies access when the conversation belongs to user other team', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $otherTeam = Team::factory()->create(['user_id' => $user->getKey()]);
    $user->teams()->attach($otherTeam, ['role' => 'admin']);

    DB::table('agent_conversations')->insert([
        'id' => 'conv-other-team',
        'user_id' => $user->getKey(),
        'team_id' => $otherTeam->getKey(),
        'title' => 'Other',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(chatChannelAuth($user, 'conv-other-team'))->toBeFalse();
});
