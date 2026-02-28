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

        Schema::create($prefix . 'workflow_run_steps', function (Blueprint $table) use ($prefix) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workflow_run_id')->constrained($prefix . 'workflow_runs')->cascadeOnDelete();
            $table->foreignUlid('workflow_node_id')->constrained($prefix . 'workflow_nodes')->cascadeOnDelete();
            $table->string('status');
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workflow_run_id', 'status']);
        });
    }
};
