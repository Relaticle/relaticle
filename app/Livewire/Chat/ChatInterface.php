<?php

declare(strict_types=1);

namespace App\Livewire\Chat;

use App\Livewire\BaseLivewireComponent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

final class ChatInterface extends BaseLivewireComponent
{
    public ?string $conversationId = null;

    /**
     * @var array<int, array{role: string, content: string, pending_actions?: array<int, mixed>}>
     */
    public array $messages = [];

    public function mount(?string $conversationId = null): void
    {
        $this->conversationId = $conversationId;

        if ($this->conversationId !== null) {
            $this->loadConversation();
        }
    }

    public function loadConversation(): void
    {
        if ($this->conversationId === null) {
            return;
        }

        $messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $this->conversationId)
            ->where('user_id', $this->authUser()->getKey())->oldest()
            ->get(['role', 'content', 'tool_calls', 'tool_results']);

        $this->messages = $messages->map(fn (object $msg): array => [
            'role' => $msg->role,
            'content' => $msg->content ?? '',
        ])->values()->all();
    }

    public function startNewConversation(): void
    {
        $this->conversationId = null;
        $this->messages = [];
    }

    public function render(): View
    {
        return view('livewire.chat.chat-interface');
    }
}
