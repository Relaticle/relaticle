<?php

declare(strict_types=1);

use App\Models\PersonalAccessToken;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;

mutates(PersonalAccessToken::class);

test('team_id can be set when initially null', function () {
    $user = User::factory()->withTeam()->create();
    $team = $user->currentTeam;

    $token = $user->tokens()->create([
        'name' => 'Test Token',
        'token' => hash('sha256', Str::random(40)),
        'abilities' => ['*'],
    ]);

    expect($token->team_id)->toBeNull();

    $token->update(['team_id' => $team->id]);

    expect($token->fresh()->team_id)->toBe($team->id);
});

test('team_id cannot be changed once set', function () {
    $user = User::factory()->withTeam()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    $token = $user->tokens()->create([
        'name' => 'Test Token',
        'token' => hash('sha256', Str::random(40)),
        'abilities' => ['*'],
        'team_id' => $team->id,
    ]);

    $token->update(['team_id' => $otherTeam->id]);
})->throws(LogicException::class, 'The team_id attribute cannot be changed after it has been set.');

test('team_id cannot be set to null once set', function () {
    $user = User::factory()->withTeam()->create();
    $team = $user->currentTeam;

    $token = $user->tokens()->create([
        'name' => 'Test Token',
        'token' => hash('sha256', Str::random(40)),
        'abilities' => ['*'],
        'team_id' => $team->id,
    ]);

    $token->update(['team_id' => null]);
})->throws(LogicException::class, 'The team_id attribute cannot be changed after it has been set.');

test('other attributes can still be updated when team_id is set', function () {
    $user = User::factory()->withTeam()->create();
    $team = $user->currentTeam;

    $token = $user->tokens()->create([
        'name' => 'Test Token',
        'token' => hash('sha256', Str::random(40)),
        'abilities' => ['*'],
        'team_id' => $team->id,
    ]);

    $token->update(['name' => 'Updated Token']);

    expect($token->fresh())
        ->name->toBe('Updated Token')
        ->team_id->toBe($team->id);
});
