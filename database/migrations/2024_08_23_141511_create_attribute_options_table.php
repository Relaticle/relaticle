<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ManukMinasyan\FilamentAttribute\Models\Attribute;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Attribute::class);
            $table->string('name')->nullable();
            $table->unsignedBigInteger('sort_order')->nullable();
            $table->timestamps();

            $table->unique(['attribute_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_options');
    }
};
