<?php

declare(strict_types=1);

namespace App\Livewire\App\Chat;

use App\Actions\Chat\ListConversations;
use App\Filament\Pages\ChatConversation;
use App\Livewire\BaseLivewireComponent;
use Illuminate\Contracts\View\View;

final class ChatSidebarNav extends BaseLivewireComponent
{
    public function render(): View
    {
        return view('livewire.app.chat.chat-sidebar-nav', [
            'conversations' => (new ListConversations)->execute($this->authUser(), 10),
            'newChatUrl' => ChatConversation::getUrl(),
        ]);
    }
}
