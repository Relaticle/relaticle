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
        Schema::create('attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->morphs('entity');
            $table->foreignIdFor(Attribute::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->text('text_value')->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->integer('integer_value')->nullable();
            $table->double('float_value')->nullable();
            $table->datetime('datetime_value')->nullable();
            $table->date('date_value')->nullable();
            $table->json('json_value')->nullable();

            $table->unique(['entity_type', 'entity_id', 'attribute_id'], 'entity_type_attribute_value_index_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
