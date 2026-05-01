<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

Broadcast::channel('chat.conversation.{conversationId}', function (User $user, string $conversationId): bool {
    if (! Str::isUuid($conversationId)) {
        return false;
    }

    $row = DB::table('agent_conversations')->where('id', $conversationId)->first();

    // First-message race fix: when the client mints a UUID and subscribes BEFORE
    // POST /chat creates the row, eagerly claim the id for THIS user so the
    // subscribe succeeds and streaming events are delivered. ChatController's
    // insertOrIgnore becomes a no-op when the row already exists with this user,
    // and its ownership check returns 403 for any other user that later tries to
    // POST against this conversation_id.
    if ($row === null) {
        DB::table('agent_conversations')->insertOrIgnore([
            'id' => $conversationId,
            'user_id' => (string) $user->getKey(),
            'team_id' => $user->current_team_id,
            'title' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('agent_conversations')->where('id', $conversationId)->first();
    }

    return $row !== null
        && $row->user_id === (string) $user->getKey()
        && ($row->team_id === null || $row->team_id === $user->current_team_id);
});
