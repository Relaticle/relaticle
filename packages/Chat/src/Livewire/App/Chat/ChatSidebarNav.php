<?php

declare(strict_types=1);

namespace Relaticle\Chat\Livewire\App\Chat;

use App\Filament\Pages\ChatConversation;
use App\Livewire\BaseLivewireComponent;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Relaticle\Chat\Actions\ListConversations;

final class ChatSidebarNav extends BaseLivewireComponent
{
    public function render(): View
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return Blade::render('<div></div>');
        }

        return view('chat::livewire.app.chat.chat-sidebar-nav', [
            'conversations' => (new ListConversations)->execute($user, 10),
            'newChatUrl' => ChatConversation::getUrl(),
        ]);
    }
}
