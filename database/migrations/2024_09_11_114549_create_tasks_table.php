<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Team::class, 'team_id');
            $table->foreignIdFor(User::class, 'user_id')->nullable();
            $table->foreignIdFor(User::class, 'assignee_id')->nullable();

            $table->string('title');

            $table->unsignedBigInteger('order_column')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
