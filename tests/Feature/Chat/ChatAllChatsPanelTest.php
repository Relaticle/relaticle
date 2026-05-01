<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Relaticle\Chat\Livewire\App\Chat\ChatAllChatsPanel;

mutates(ChatAllChatsPanel::class);

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($this->user);
    Filament::setTenant($this->user->currentTeam);
});

it('mounts closed by default', function (): void {
    $component = Livewire::test(ChatAllChatsPanel::class);

    expect($component->get('isOpen'))->toBeFalse();
    expect($component->get('search'))->toBe('');
});

it('opens when chat:open-all-chats event is received', function (): void {
    Livewire::test(ChatAllChatsPanel::class)
        ->dispatch('chat:open-all-chats')
        ->assertSet('isOpen', true);
});

it('closes when chat:close-all-chats event is received', function (): void {
    Livewire::test(ChatAllChatsPanel::class)
        ->set('isOpen', true)
        ->set('search', 'acme')
        ->dispatch('chat:close-all-chats')
        ->assertSet('isOpen', false)
        ->assertSet('search', '');
});

it('deletes a conversation via livewire action', function (): void {
    DB::table('agent_conversations')->insert([
        'id' => 'cap-del',
        'user_id' => $this->user->getKey(),
        'team_id' => $this->user->current_team_id,
        'title' => 'Delete me',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(ChatAllChatsPanel::class)
        ->call('deleteConversation', 'cap-del')
        ->assertDispatched('chat:conversation-deleted');

    expect(DB::table('agent_conversations')->where('id', 'cap-del')->exists())->toBeFalse();
});
