<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            DELETE FROM ai_credit_transactions a
            USING ai_credit_transactions b
            WHERE a.id < b.id
              AND a.team_id = b.team_id
              AND a.conversation_id IS NOT DISTINCT FROM b.conversation_id
              AND a.created_at = b.created_at
        SQL);

        Schema::table('ai_credit_transactions', function (Blueprint $table): void {
            $table->unique(
                ['team_id', 'conversation_id', 'created_at'],
                'ai_credit_tx_unique_per_conv_per_instant'
            );
        });
    }
};
