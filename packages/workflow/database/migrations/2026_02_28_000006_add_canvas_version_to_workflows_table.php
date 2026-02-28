<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('workflow.table_prefix');

        Schema::table("{$prefix}workflows", function (Blueprint $table) {
            $table->unsignedInteger('canvas_version')->default(1)->after('canvas_data');
        });
    }

    public function down(): void
    {
        $prefix = config('workflow.table_prefix');

        Schema::table("{$prefix}workflows", function (Blueprint $table) {
            $table->dropColumn('canvas_version');
        });
    }
};
