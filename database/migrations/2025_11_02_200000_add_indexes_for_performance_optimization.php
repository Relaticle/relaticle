<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes for creation_source which is frequently filtered
        Schema::table('companies', function (Blueprint $table): void {
            $table->index('creation_source');
        });

        Schema::table('people', function (Blueprint $table): void {
            $table->index('creation_source');
        });

        Schema::table('opportunities', function (Blueprint $table): void {
            $table->index('creation_source');
            // Composite index for common query pattern: created_at + creation_source
            $table->index(['created_at', 'creation_source']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->index('creation_source');
        });

        Schema::table('notes', function (Blueprint $table): void {
            $table->index('creation_source');
        });

        // Add composite index for creator_id which is frequently used with creation_source
        Schema::table('tasks', function (Blueprint $table): void {
            $table->index(['creator_id', 'creation_source']);
        });

        Schema::table('opportunities', function (Blueprint $table): void {
            $table->index(['creator_id', 'creation_source']);
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->index(['creator_id', 'creation_source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex(['creation_source']);
            $table->dropIndex(['creator_id', 'creation_source']);
        });

        Schema::table('people', function (Blueprint $table): void {
            $table->dropIndex(['creation_source']);
        });

        Schema::table('opportunities', function (Blueprint $table): void {
            $table->dropIndex(['creation_source']);
            $table->dropIndex(['created_at', 'creation_source']);
            $table->dropIndex(['creator_id', 'creation_source']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex(['creation_source']);
            $table->dropIndex(['creator_id', 'creation_source']);
        });

        Schema::table('notes', function (Blueprint $table): void {
            $table->dropIndex(['creation_source']);
        });
    }
};
