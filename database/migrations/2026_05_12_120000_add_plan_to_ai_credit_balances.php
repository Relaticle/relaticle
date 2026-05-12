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
        Schema::table('ai_credit_balances', function (Blueprint $table): void {
            $table->string('plan', 32)->default('free')->after('credits_used');
        });

        DB::statement("UPDATE ai_credit_balances SET plan = 'free' WHERE plan IS NULL");
        DB::statement("ALTER TABLE ai_credit_balances ADD CONSTRAINT ai_credit_balances_plan_known CHECK (plan IN ('free','starter','pro','enterprise'))");
    }
};
