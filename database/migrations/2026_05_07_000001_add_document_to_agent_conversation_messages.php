<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a TipTap document JSON column to chat messages.
 *
 * Alpha-phase migration: truncates existing chat data so the column can be
 * NOT NULL from day one. If this migration is ever needed against a database
 * with chat data worth preserving, replace the truncate with a backfill job
 * that walks each message's content + mention rows to reconstruct a
 * document JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Children first so FK constraints don't trip. CASCADE so any rows
        // we missed in the explicit list go too.
        DB::statement('TRUNCATE TABLE agent_conversation_message_mentions, pending_actions, ai_credit_transactions, agent_conversation_messages, agent_conversations RESTART IDENTITY CASCADE');

        Schema::table('agent_conversation_messages', function (Blueprint $table): void {
            $table->jsonb('document')->after('content');
        });
    }
};
