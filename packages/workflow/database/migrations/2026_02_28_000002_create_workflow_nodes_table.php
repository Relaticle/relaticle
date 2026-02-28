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

        Schema::create($prefix . 'workflow_nodes', function (Blueprint $table) use ($prefix) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workflow_id')->constrained($prefix . 'workflows')->cascadeOnDelete();
            $table->string('node_id');
            $table->string('type');
            $table->string('action_type')->nullable();
            $table->json('config')->nullable();
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->timestamps();

            $table->unique(['workflow_id', 'node_id']);
        });
    }
};
