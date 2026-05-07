<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Relaticle\Chat\Jobs\ProcessChatMessage;
use Relaticle\Chat\Models\AgentConversation;
use Relaticle\Chat\Models\AiCreditBalance;
use Tests\Helpers\ChatDocument;

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

it('creates a conversation, dispatches the message, and returns the new id', function (): void {
    Queue::fake();

    $response = $this->postJson(route('chat.conversations.create'), [
        'document' => ChatDocument::fromText('Hello world'),
    ])->assertOk();

    $conversationId = $response->json('conversation_id');
    expect($conversationId)->toBeString()->not->toBeEmpty();

    expect(AgentConversation::query()->find($conversationId))->not->toBeNull();
    expect(AgentConversation::query()->find($conversationId)->title)->toBe('Hello world');

    Queue::assertPushed(ProcessChatMessage::class, function ($job) use ($conversationId): bool {
        return $job->conversationId === $conversationId
            && $job->message === 'Hello world';
    });
});

it('returns 422 when document is missing', function (): void {
    $this->postJson(route('chat.conversations.create'), [])->assertStatus(422);
});

it('returns 422 when document text is empty', function (): void {
    $this->postJson(route('chat.conversations.create'), [
        'document' => ['type' => 'doc', 'content' => []],
    ])->assertStatus(422);
});

it('returns 402 when credit balance is insufficient', function (): void {
    AiCreditBalance::query()->where('team_id', $this->team->getKey())->update([
        'credits_remaining' => 0,
    ]);

    $response = $this->postJson(route('chat.conversations.create'), [
        'document' => ChatDocument::fromText('Hi'),
    ])->assertStatus(402);

    expect($response->json('error'))->toBe('credits_exhausted');
});

it('filters cross-tenant mention IDs from the document before persisting', function (): void {
    Queue::fake();
    $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
    $foreignCompany = Company::factory()->for($otherTeam)->create(['name' => 'Foreign']);

    $document = ChatDocument::fromText('Hi ', [
        ['type' => 'company', 'id' => $foreignCompany->getKey(), 'label' => 'Foreign'],
    ]);

    $this->postJson(route('chat.conversations.create'), [
        'document' => $document,
    ])->assertOk();

    Queue::assertPushed(ProcessChatMessage::class, function ($job): bool {
        return $job->mentions === [];
    });
});

it('returns 422 when document text exceeds 5000 characters', function (): void {
    $longText = str_repeat('a', 5001);

    $this->postJson(route('chat.conversations.create'), [
        'document' => ChatDocument::fromText($longText),
    ])->assertStatus(422)
        ->assertJsonPath('errors.document.0', 'Message is too long.');

    expect(AgentConversation::query()->count())->toBe(0);
});

it('does not consume a credit when the document is empty', function (): void {
    $balanceBefore = AiCreditBalance::query()->where('team_id', $this->team->getKey())->first();

    $this->postJson(route('chat.conversations.create'), [
        'document' => ['type' => 'doc', 'content' => []],
    ])->assertStatus(422);

    $balanceAfter = AiCreditBalance::query()->where('team_id', $this->team->getKey())->first();
    expect($balanceAfter->credits_remaining)->toBe($balanceBefore->credits_remaining);
});

it('does not consume a credit when the document text is too long', function (): void {
    $balanceBefore = AiCreditBalance::query()->where('team_id', $this->team->getKey())->first();

    $this->postJson(route('chat.conversations.create'), [
        'document' => ChatDocument::fromText(str_repeat('a', 5001)),
    ])->assertStatus(422);

    $balanceAfter = AiCreditBalance::query()->where('team_id', $this->team->getKey())->first();
    expect($balanceAfter->credits_remaining)->toBe($balanceBefore->credits_remaining);
});

it('returns 403 when the user has no current team', function (): void {
    $teamlessUser = User::factory()->create(['current_team_id' => null]);
    $this->actingAs($teamlessUser);

    $this->postJson(route('chat.conversations.create'), [
        'document' => ChatDocument::fromText('Hi'),
    ])->assertStatus(403);
});
