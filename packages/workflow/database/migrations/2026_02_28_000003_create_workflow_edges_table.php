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

        Schema::create($prefix . 'workflow_edges', function (Blueprint $table) use ($prefix) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workflow_id')->constrained($prefix . 'workflows')->cascadeOnDelete();
            $table->string('edge_id');
            $table->foreignUlid('source_node_id')->constrained($prefix . 'workflow_nodes')->cascadeOnDelete();
            $table->foreignUlid('target_node_id')->constrained($prefix . 'workflow_nodes')->cascadeOnDelete();
            $table->string('condition_label')->nullable();
            $table->json('condition_config')->nullable();
            $table->timestamps();

            $table->unique(['workflow_id', 'edge_id']);
        });
    }
};
