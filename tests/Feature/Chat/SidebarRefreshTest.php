<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Relaticle\Chat\Livewire\App\Chat\ChatSidebarNav;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($this->user);
    Filament::setTenant($this->user->currentTeam);
});

it('registers chat:conversation-created listener', function (): void {
    $instance = Livewire::test(ChatSidebarNav::class)->instance();

    $reflection = new ReflectionMethod($instance, 'getListeners');
    $listeners = $reflection->invoke($instance);

    expect($listeners)->toHaveKey('chat:conversation-created', '$refresh');
});

it('registers chat:conversation-deleted listener', function (): void {
    $instance = Livewire::test(ChatSidebarNav::class)->instance();

    $reflection = new ReflectionMethod($instance, 'getListeners');
    $listeners = $reflection->invoke($instance);

    expect($listeners)->toHaveKey('chat:conversation-deleted', '$refresh');
});

it('registers chat:conversation-renamed listener', function (): void {
    $instance = Livewire::test(ChatSidebarNav::class)->instance();

    $reflection = new ReflectionMethod($instance, 'getListeners');
    $listeners = $reflection->invoke($instance);

    expect($listeners)->toHaveKey('chat:conversation-renamed', '$refresh');
});

it('deletes a conversation via livewire action', function (): void {
    DB::table('agent_conversations')->insert([
        'id' => 'c-del',
        'user_id' => $this->user->getKey(),
        'team_id' => $this->user->current_team_id,
        'title' => 'Kill me',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(ChatSidebarNav::class)
        ->call('deleteConversation', 'c-del')
        ->assertDispatched('chat:conversation-deleted');

    expect(DB::table('agent_conversations')->where('id', 'c-del')->exists())->toBeFalse();
});

it('shows empty state when no conversations exist', function (): void {
    Livewire::test(ChatSidebarNav::class)
        ->assertSee('No chats yet');
});

it('renders an "All chats" trigger that opens the second sidebar', function (): void {
    DB::table('agent_conversations')->insert([
        'id' => 'all1',
        'user_id' => $this->user->getKey(),
        'team_id' => $this->user->current_team_id,
        'title' => 'Existing chat',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(ChatSidebarNav::class)
        ->assertSee('All chats')
        ->assertSeeHtml("dispatchEvent(new CustomEvent('chat:open-all-chats'))");
});
