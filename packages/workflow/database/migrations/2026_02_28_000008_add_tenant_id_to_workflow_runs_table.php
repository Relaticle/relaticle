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
        $prefix = config('workflow.table_prefix', '');

        Schema::table($prefix . 'workflow_runs', function (Blueprint $table) use ($prefix) {
            $table->string('tenant_id')->nullable()->after('id');
            $table->index('tenant_id');
        });

        // Populate tenant_id from parent workflow
        DB::table($prefix . 'workflow_runs')
            ->whereNull('tenant_id')
            ->update([
                'tenant_id' => DB::raw(
                    '(SELECT tenant_id FROM ' . $prefix . 'workflows WHERE ' . $prefix . 'workflows.id = ' . $prefix . 'workflow_runs.workflow_id)'
                ),
            ]);
    }

    public function down(): void
    {
        $prefix = config('workflow.table_prefix', '');

        Schema::table($prefix . 'workflow_runs', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
