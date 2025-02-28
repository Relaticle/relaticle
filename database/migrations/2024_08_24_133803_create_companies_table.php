<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Account Owner For Companies: Your team member responsible for managing the company account
            $table->foreignId('account_owner_id')->nullable()->constrained('users');

            $table->string('name');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
