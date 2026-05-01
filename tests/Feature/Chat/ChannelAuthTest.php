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

const CONV_MINE = '019dded5-aaaa-7bbb-8ccc-444400000001';
const CONV_OTHER = '019dded5-aaaa-7bbb-8ccc-444400000002';
const CONV_OTHER_TEAM = '019dded5-aaaa-7bbb-8ccc-444400000003';
const CONV_FRESH = '019dded5-aaaa-7bbb-8ccc-444400000004';

it('grants access to own conversation channel', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    DB::table('agent_conversations')->insert([
        'id' => CONV_MINE,
        'user_id' => $user->getKey(),
        'team_id' => $user->current_team_id,
        'title' => 'Mine',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(chatChannelAuth($user, CONV_MINE))->toBeTrue();
});

it('denies access to another user conversation channel', function (): void {
    $mine = User::factory()->withPersonalTeam()->create();
    $other = User::factory()->withPersonalTeam()->create();

    DB::table('agent_conversations')->insert([
        'id' => CONV_OTHER,
        'user_id' => $other->getKey(),
        'team_id' => $other->current_team_id,
        'title' => 'Other',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(chatChannelAuth($mine, CONV_OTHER))->toBeFalse();
});

it('rejects channel ids that are not UUIDs', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    expect(chatChannelAuth($user, 'not-a-uuid'))->toBeFalse();
});

it('optimistically claims a fresh UUID for the authenticated user when the row does not exist', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    expect(chatChannelAuth($user, CONV_FRESH))->toBeTrue();

    $row = DB::table('agent_conversations')->where('id', CONV_FRESH)->first();

    expect($row)->not->toBeNull()
        ->and($row->user_id)->toBe((string) $user->getKey())
        ->and($row->team_id)->toBe($user->current_team_id);
});

it('refuses optimistic claim when another user already holds the conversation id', function (): void {
    $owner = User::factory()->withPersonalTeam()->create();
    $eve = User::factory()->withPersonalTeam()->create();

    // Owner claims first (e.g. via their own subscribe attempt or POST).
    DB::table('agent_conversations')->insert([
        'id' => CONV_FRESH,
        'user_id' => $owner->getKey(),
        'team_id' => $owner->current_team_id,
        'title' => 'Owner',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Eve later tries to subscribe to the same id — must be denied.
    expect(chatChannelAuth($eve, CONV_FRESH))->toBeFalse();
});

it('denies access when the conversation belongs to user other team', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $otherTeam = Team::factory()->create(['user_id' => $user->getKey()]);
    $user->teams()->attach($otherTeam, ['role' => 'admin']);

    DB::table('agent_conversations')->insert([
        'id' => CONV_OTHER_TEAM,
        'user_id' => $user->getKey(),
        'team_id' => $otherTeam->getKey(),
        'title' => 'Other',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(chatChannelAuth($user, CONV_OTHER_TEAM))->toBeFalse();
});
