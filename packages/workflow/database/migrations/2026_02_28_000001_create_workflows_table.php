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

        Schema::create($prefix . 'workflows', function (Blueprint $table) use ($prefix) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type');
            $table->json('trigger_config')->nullable();
            $table->json('canvas_data')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active', 'trigger_type']);
        });
    }
};
