<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds a JSONB default to `document` so the laravel/ai ConversationStore can
 * insert user/assistant messages without supplying the column. ProcessChatMessage
 * later overwrites the user row's document with the real TipTap payload.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE agent_conversation_messages
            ALTER COLUMN document SET DEFAULT '{"type":"doc","content":[]}'::jsonb
        SQL);
    }
};
