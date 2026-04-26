<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Cache;

it('marks a conversation as cancelled when cancel endpoint hit', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    $conversationId = 'test-conv-cancel';
    $cacheKey = "chat:cancel:{$conversationId}";

    expect(Cache::has($cacheKey))->toBeFalse();

    $this->postJson(route('chat.cancel', ['conversationId' => $conversationId]))
        ->assertOk()
        ->assertJson(['cancelled' => true]);

    expect(Cache::get($cacheKey))->toBe((string) $user->getKey());
});

it('overwrites the cancel marker with the requesting user id', function (): void {
    $userA = User::factory()->withPersonalTeam()->create();
    $userB = User::factory()->withPersonalTeam()->create();

    $this->actingAs($userA);
    $conversationId = 'test-conv-other-user';
    Cache::put("chat:cancel:{$conversationId}", (string) $userA->getKey(), 60);

    $this->actingAs($userB);
    $this->postJson(route('chat.cancel', ['conversationId' => $conversationId]))
        ->assertOk();

    expect(Cache::get("chat:cancel:{$conversationId}"))->toBe((string) $userB->getKey());
});

it('returns 401 for unauthenticated cancel', function (): void {
    $this->postJson(route('chat.cancel', ['conversationId' => 'x']))
        ->assertUnauthorized();
});
