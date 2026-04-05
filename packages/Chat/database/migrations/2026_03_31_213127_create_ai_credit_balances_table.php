<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_credit_balances', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('credits_remaining')->default(0);
            $table->unsignedInteger('credits_used')->default(0);
            $table->timestamp('period_starts_at');
            $table->timestamp('period_ends_at');
            $table->timestamps();

            $table->unique('team_id');
        });
    }
};
