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

        Schema::create($prefix . 'workflow_runs', function (Blueprint $table) use ($prefix) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workflow_id')->constrained($prefix . 'workflows')->cascadeOnDelete();
            $table->string('trigger_record_type')->nullable();
            $table->string('trigger_record_id')->nullable();
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('context_data')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'status']);
            $table->index(['trigger_record_type', 'trigger_record_id']);
        });
    }
};
