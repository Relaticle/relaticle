<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Relaticle\Chat\Filament\Pages\ChatsIndex;
use Relaticle\Chat\Models\AgentConversation;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);
    Filament::setTenant($this->team);
});

it('lists conversations for current team', function (): void {
    DB::table('agent_conversations')->insert([
        ['id' => 'c1', 'user_id' => $this->user->getKey(), 'team_id' => $this->team->getKey(), 'title' => 'First', 'created_at' => now()->subMinutes(5), 'updated_at' => now()->subMinutes(5)],
        ['id' => 'c2', 'user_id' => $this->user->getKey(), 'team_id' => $this->team->getKey(), 'title' => 'Second', 'created_at' => now(), 'updated_at' => now()],
    ]);

    Livewire::test(ChatsIndex::class)
        ->assertCanSeeTableRecords(AgentConversation::query()->whereIn('id', ['c1', 'c2'])->get());
});

it('does not list other teams conversations', function (): void {
    $otherUser = User::factory()->withPersonalTeam()->create();

    DB::table('agent_conversations')->insert([
        'id' => 'other',
        'user_id' => $otherUser->getKey(),
        'team_id' => $otherUser->currentTeam->getKey(),
        'title' => 'Not mine',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(ChatsIndex::class)
        ->assertCanNotSeeTableRecords(AgentConversation::query()->whereIn('id', ['other'])->get());
});

it('shows empty state when no conversations exist', function (): void {
    Livewire::test(ChatsIndex::class)->assertSee('Start your first chat');
});
