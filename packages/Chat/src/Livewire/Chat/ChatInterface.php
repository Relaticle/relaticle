<?php

declare(strict_types=1);

namespace Relaticle\Chat\Livewire\Chat;

use Relaticle\Chat\Actions\ListConversationMessages;
use App\Livewire\BaseLivewireComponent;
use Illuminate\Contracts\View\View;

final class ChatInterface extends BaseLivewireComponent
{
    public ?string $conversationId = null;

    public ?string $initialMessage = null;

    /**
     * @var array<int, array{role: string, content: string, pending_actions?: array<int, mixed>}>
     */
    public array $messages = [];

    public function mount(?string $conversationId = null, ?string $initialMessage = null): void
    {
        $this->conversationId = $conversationId;
        $this->initialMessage = $initialMessage;

        if ($this->conversationId !== null) {
            $this->messages = $this->fetchMessages();
        }
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public function fetchMessages(): array
    {
        if ($this->conversationId === null) {
            return [];
        }

        return (new ListConversationMessages)->execute(
            $this->authUser(),
            $this->conversationId,
        );
    }

    public function render(): View
    {
        return view('chat::livewire.chat.chat-interface');
    }
}
