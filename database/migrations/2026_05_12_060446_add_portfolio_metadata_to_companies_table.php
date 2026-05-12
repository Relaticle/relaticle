<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('partner_source', 50)->nullable()->after('name');
            $table->string('geography', 2)->nullable()->after('partner_source');
            $table->decimal('concentration_percentage', 5, 2)->nullable()->after('geography');
            $table->boolean('is_recurring')->default(false)->after('concentration_percentage');

            $table->index('partner_source', 'idx_companies_partner_source');
            $table->index('geography', 'idx_companies_geography');
        });

        DB::statement(
            'ALTER TABLE companies ADD CONSTRAINT chk_companies_concentration_pct '
            .'CHECK (concentration_percentage IS NULL OR (concentration_percentage >= 0 AND concentration_percentage <= 100))'
        );
    }
};
