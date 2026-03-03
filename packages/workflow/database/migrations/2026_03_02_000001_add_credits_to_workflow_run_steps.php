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

        Schema::table($prefix . 'workflow_run_steps', function (Blueprint $blueprint) {
            $blueprint->unsignedInteger('credits_used')->default(0)->after('output_data');
        });

        Schema::table($prefix . 'workflow_runs', function (Blueprint $blueprint) {
            $blueprint->unsignedInteger('total_credits_used')->default(0)->after('context_data');
        });

        Schema::table($prefix . 'workflows', function (Blueprint $blueprint) {
            $blueprint->unsignedInteger('max_credits_per_run')->default(50)->after('canvas_version');
        });
    }

    public function down(): void
    {
        $prefix = config('workflow.table_prefix', '');

        Schema::table($prefix . 'workflow_run_steps', function (Blueprint $blueprint) {
            $blueprint->dropColumn('credits_used');
        });

        Schema::table($prefix . 'workflow_runs', function (Blueprint $blueprint) {
            $blueprint->dropColumn('total_credits_used');
        });

        Schema::table($prefix . 'workflows', function (Blueprint $blueprint) {
            $blueprint->dropColumn('max_credits_per_run');
        });
    }
};
