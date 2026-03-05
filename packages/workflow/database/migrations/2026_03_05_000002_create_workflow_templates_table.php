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

        Schema::create($prefix . 'workflow_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('icon')->nullable();
            $table->json('definition');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        $prefix = config('workflow.table_prefix', '');

        Schema::dropIfExists($prefix . 'workflow_templates');
    }
};
