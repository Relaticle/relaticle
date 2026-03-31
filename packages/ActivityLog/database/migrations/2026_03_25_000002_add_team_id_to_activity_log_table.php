<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->foreignUlid('team_id')->nullable()->after('id')->constrained('teams')->cascadeOnDelete();
            $table->index(['subject_type', 'subject_id', 'created_at'], 'idx_activity_log_subject_timeline');
            $table->index(['team_id', 'created_at'], 'idx_activity_log_team_activity');
        });
    }
};
