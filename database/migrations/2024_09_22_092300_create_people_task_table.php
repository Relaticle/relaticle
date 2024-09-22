<?php

use App\Models\People;
use App\Models\Task;
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
        Schema::create('people_task', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(People::class, 'people_id');
            $table->foreignIdFor(Task::class, 'task_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people_task');
    }
};
