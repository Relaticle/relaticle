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
        Schema::table('teams', function (Blueprint $table): void {
            $table->dropColumn('onboarding_role');
            $table->json('onboarding_context')->nullable();
            $table->string('onboarding_referral_source')->nullable();
        });

        DB::table('teams')
            ->where('onboarding_use_case', 'sales_pipeline')
            ->update(['onboarding_use_case' => 'sales']);

        DB::table('teams')
            ->where('onboarding_use_case', 'general')
            ->update(['onboarding_use_case' => 'other']);
    }
};
