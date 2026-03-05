<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('workflow.table_prefix', '');

        Schema::table($prefix . 'workflow_run_steps', function (Blueprint $table) {
            $table->unsignedInteger('duration_ms')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        $prefix = config('workflow.table_prefix', '');

        Schema::table($prefix . 'workflow_run_steps', function (Blueprint $table) {
            $table->dropColumn('duration_ms');
        });
    }
};
