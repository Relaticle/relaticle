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

        Schema::create($prefix . 'workflow_favorites', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->string('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('workflow_id', 26);
            $table->foreign('workflow_id')->references('id')->on($prefix . 'workflows')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'workflow_id']);
        });
    }

    public function down(): void
    {
        $prefix = config('workflow.table_prefix', '');
        Schema::dropIfExists($prefix . 'workflow_favorites');
    }
};
