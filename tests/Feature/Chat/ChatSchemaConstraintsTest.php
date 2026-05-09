<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('cascades agent_conversations when the team is hard-deleted', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    $conversationId = (string) Str::uuid7();
    DB::table('agent_conversations')->insert([
        'id' => $conversationId,
        'user_id' => (string) $user->getKey(),
        'team_id' => $team->getKey(),
        'title' => 'Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('agent_conversations')->where('id', $conversationId)->count())->toBe(1);

    $team->forceDelete();

    expect(DB::table('agent_conversations')->where('id', $conversationId)->count())->toBe(0);
});
