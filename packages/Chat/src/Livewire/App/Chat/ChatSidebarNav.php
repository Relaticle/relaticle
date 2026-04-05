<?php

declare(strict_types=1);

namespace Relaticle\Chat\Livewire\App\Chat;

use Relaticle\Chat\Actions\ListConversations;
use App\Filament\Pages\ChatConversation;
use App\Livewire\BaseLivewireComponent;
use Illuminate\Contracts\View\View;

final class ChatSidebarNav extends BaseLivewireComponent
{
    public function render(): View
    {
        return view('chat::livewire.app.chat.chat-sidebar-nav', [
            'conversations' => (new ListConversations)->execute($this->authUser(), 10),
            'newChatUrl' => ChatConversation::getUrl(),
        ]);
    }
}
