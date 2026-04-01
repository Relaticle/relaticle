<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteConversation
{
    public function execute(User $user, string $conversationId): bool
    {
        $deleted = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $user->getKey())
            ->delete();

        if ($deleted === 0) {
            return false;
        }

        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->delete();

        return true;
    }
}
