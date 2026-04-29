<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

Broadcast::channel('chat.conversation.{conversationId}', fn (User $user, string $conversationId): bool => DB::table('agent_conversations')
    ->where('id', $conversationId)
    ->where('user_id', $user->getKey())
    ->where('team_id', $user->current_team_id)
    ->exists());
