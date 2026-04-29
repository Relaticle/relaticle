<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Migrations\AiMigration;

return new class extends AiMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agent_conversations', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->char('user_id', 26)->nullable()->index();
            $table->string('title');
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('agent_conversation_messages', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('conversation_id', 36)->index();
            $table->char('user_id', 26)->nullable()->index();
            $table->string('agent');
            $table->string('role', 25);
            $table->text('content');
            $table->jsonb('attachments');
            $table->jsonb('tool_calls');
            $table->jsonb('tool_results');
            $table->jsonb('usage');
            $table->jsonb('meta');
            $table->timestamps();

            $table->index(['conversation_id', 'user_id', 'updated_at'], 'conversation_index');

            $table->foreign('conversation_id')->references('id')->on('agent_conversations')->cascadeOnDelete();
        });
    }
};
