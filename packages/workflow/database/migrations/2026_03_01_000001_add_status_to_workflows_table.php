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
        $table = config('workflow.table_prefix', '') . 'workflows';

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->string('status')->default('draft')->after('is_active');
            $blueprint->timestamp('published_at')->nullable()->after('status');
        });

        // Data migration: convert is_active boolean to status enum
        DB::table($table)
            ->where('is_active', true)
            ->update([
                'status' => 'live',
                'published_at' => DB::raw('updated_at'),
            ]);

        DB::table($table)
            ->where('is_active', false)
            ->update([
                'status' => 'draft',
            ]);

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            $blueprint->dropIndex($table . '_tenant_id_is_active_trigger_type_index');
        });

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropColumn('is_active');
        });

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            $blueprint->index(['tenant_id', 'status', 'trigger_type']);
        });
    }

    public function down(): void
    {
        $table = config('workflow.table_prefix', '') . 'workflows';

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->boolean('is_active')->default(false)->after('webhook_secret');
        });

        // Reverse data migration: convert status back to is_active
        DB::table($table)
            ->where('status', 'live')
            ->update(['is_active' => true]);

        DB::table($table)
            ->where('status', '!=', 'live')
            ->update(['is_active' => false]);

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            $blueprint->dropIndex($table . '_tenant_id_status_trigger_type_index');
        });

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropColumn(['status', 'published_at']);
        });

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->index(['tenant_id', 'is_active', 'trigger_type']);
        });
    }
};
