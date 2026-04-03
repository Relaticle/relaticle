<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->string('onboarding_role')->nullable()->after('personal_team');
            $table->string('onboarding_use_case')->nullable()->after('onboarding_role');
        });
    }
};
