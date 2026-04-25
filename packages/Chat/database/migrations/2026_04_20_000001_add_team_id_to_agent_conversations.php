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
        Schema::table('agent_conversations', function (Blueprint $table): void {
            $table->char('team_id', 26)->nullable()->after('user_id');
            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
            $table->index(['team_id', 'user_id', 'updated_at']);
        });

        DB::statement(<<<'SQL'
            UPDATE agent_conversations ac
            SET team_id = u.current_team_id
            FROM users u
            WHERE ac.user_id = u.id AND ac.team_id IS NULL
        SQL);
    }
};
