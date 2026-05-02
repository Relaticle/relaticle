<?php

declare(strict_types=1);

use App\Models\Note;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Relaticle\Chat\Http\Controllers\ChatController;
use Relaticle\Chat\Jobs\ProcessChatMessage;
use Relaticle\Chat\Models\AiCreditBalance;

mutates(ChatController::class);

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);
    Filament::setTenant($this->team);
    RateLimiter::clear('60|'.request()->ip());

    AiCreditBalance::query()->create([
        'team_id' => $this->team->getKey(),
        'credits_remaining' => 100,
        'credits_used' => 0,
        'period_starts_at' => now()->startOfMonth(),
        'period_ends_at' => now()->endOfMonth(),
    ]);
});

it('returns matching notes from the mentions endpoint', function (): void {
    $note = Note::factory()->for($this->team)->create(['title' => 'Customer feedback summary']);

    $response = $this->getJson(route('chat.mentions', ['q' => 'feedback']))->assertOk();

    $matches = collect($response->json('data'))->where('type', 'note');
    expect($matches)->toHaveCount(1);
    expect($matches->first()['id'])->toBe($note->id);
    expect($matches->first()['name'])->toBe('Customer feedback summary');
});

it('accepts a note type in the send mentions payload', function (): void {
    Queue::fake();
    $note = Note::factory()->for($this->team)->create(['title' => 'Q3 retro']);

    $response = $this->postJson(route('chat.send'), [
        'message' => 'Summarize @Q3_retro',
        'mentions' => [['type' => 'note', 'id' => $note->id]],
    ])->assertOk();

    Queue::assertPushed(ProcessChatMessage::class, function (ProcessChatMessage $job) use ($note): bool {
        return count($job->mentions) === 1
            && $job->mentions[0]['type'] === 'note'
            && $job->mentions[0]['id'] === $note->id
            && $job->mentions[0]['label'] === 'Q3 retro';
    });
});

it('drops note mentions belonging to another team', function (): void {
    Queue::fake();
    $otherTeam = Team::factory()->create();
    $foreignNote = Note::factory()->for($otherTeam)->create(['title' => 'Cross-team']);

    $this->postJson(route('chat.send'), [
        'message' => 'Tell me about that note',
        'mentions' => [['type' => 'note', 'id' => $foreignNote->id]],
    ])->assertOk();

    Queue::assertPushed(ProcessChatMessage::class, function (ProcessChatMessage $job): bool {
        return $job->mentions === [];
    });
});
