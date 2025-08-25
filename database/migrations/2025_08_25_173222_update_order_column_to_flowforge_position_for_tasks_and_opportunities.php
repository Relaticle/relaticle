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
        // Update tasks table
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('order_column');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->flowforgePositionColumn('order_column');
        });

        // Update opportunities table
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn('order_column');
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->flowforgePositionColumn('order_column');
        });
    }
};
