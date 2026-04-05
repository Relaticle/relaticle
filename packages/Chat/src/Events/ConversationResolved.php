<?php

declare(strict_types=1);

namespace Relaticle\Chat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final class ConversationResolved implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function __construct(
        public readonly string $userId,
        public readonly string $conversationId,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("chat.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.resolved';
    }
}
