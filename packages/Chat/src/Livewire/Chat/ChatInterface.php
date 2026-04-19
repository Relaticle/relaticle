<?php

declare(strict_types=1);

namespace Relaticle\Chat\Livewire\Chat;

use App\Livewire\BaseLivewireComponent;
use Illuminate\Contracts\View\View;
use Relaticle\Chat\Actions\ListConversationMessages;

final class ChatInterface extends BaseLivewireComponent
{
    public ?string $conversationId = null;

    public ?string $initialMessage = null;

    public ?string $oldestMessageId = null;

    public bool $hasMoreMessages = false;

    private const int PAGE_SIZE = 50;

    /**
     * @var array<int, array{id?: string, role: string, content: string, pending_actions?: array<int, mixed>}>
     */
    public array $messages = [];

    public function mount(?string $conversationId = null, ?string $initialMessage = null): void
    {
        $this->conversationId = $conversationId;

        /** @var string|null $promptQuery */
        $promptQuery = request()->query('prompt');
        $this->initialMessage = $initialMessage ?? $promptQuery;

        if ($this->conversationId !== null) {
            $this->messages = $this->fetchMessages();
            $this->oldestMessageId = $this->messages === [] ? null : ($this->messages[0]['id'] ?? null);
            $this->hasMoreMessages = count($this->messages) === self::PAGE_SIZE;
        }
    }

    /**
     * @return array<int, array{id: string, role: string, content: string, pending_actions: array<int, mixed>}>
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

    public function loadEarlierMessages(): void
    {
        if ($this->conversationId === null || $this->oldestMessageId === null) {
            return;
        }

        $earlier = (new ListConversationMessages)->execute(
            $this->authUser(),
            $this->conversationId,
            beforeMessageId: $this->oldestMessageId,
        );

        $this->messages = [...$earlier, ...$this->messages];
        $this->oldestMessageId = $this->messages === [] ? null : ($this->messages[0]['id'] ?? $this->oldestMessageId);
        $this->hasMoreMessages = count($earlier) === self::PAGE_SIZE;

        $this->dispatch('chat:messages-prepended', messages: $earlier, hasMore: $this->hasMoreMessages);
    }

    public function render(): View
    {
        return view('chat::livewire.chat.chat-interface');
    }
}
