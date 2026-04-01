<?php

declare(strict_types=1);

namespace App\Livewire\Chat;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ChatSidePanel extends Component
{
    public bool $open = false;

    public ?string $conversationId = null;

    /**
     * @var array<string, mixed>
     */
    protected $listeners = [
        'openChatPanel' => 'openPanel',
        'closeChatPanel' => 'closePanel',
    ];

    public function openPanel(?string $conversationId = null): void
    {
        $this->open = true;
        $this->conversationId = $conversationId;
    }

    public function closePanel(): void
    {
        $this->open = false;
    }

    public function togglePanel(): void
    {
        $this->open = ! $this->open;
    }

    public function render(): View
    {
        return view('livewire.chat.chat-side-panel');
    }
}
