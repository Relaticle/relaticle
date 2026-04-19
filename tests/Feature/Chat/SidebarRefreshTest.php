<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
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
