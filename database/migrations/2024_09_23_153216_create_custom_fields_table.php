<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Relaticle\CustomFields\Models\CustomField;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Custom Fields
         */
        Schema::create(config('custom-fields.table_names.custom_fields'), function (Blueprint $table): void {
            $table->id();

            if (config('custom-fields.tenant_aware', false) && Filament::hasTenancy()) {
                $table->foreignId(config('custom-fields.column_names.tenant_foreign_key'))->nullable()->index();
            }

            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->string('lookup_type')->nullable();
            $table->string('entity_type');
            $table->unsignedBigInteger('sort_order')->nullable();
            $table->json('validation_rules')->nullable();

            $table->boolean('active')->default(1);
            $table->boolean('user_defined')->default(1);

            $table->unique(['code', 'entity_type']);

            $table->softDeletes();
            $table->timestamps();
        });

        /**
         * Custom Field Options
         */
        Schema::create(config('custom-fields.table_names.custom_field_options'), function (Blueprint $table): void {
            $table->id();

            if (config('custom-fields.tenant_aware', false) && Filament::hasTenancy()) {
                $table->foreignId(config('custom-fields.column_names.tenant_foreign_key'))->nullable()->index();
            }

            $table->foreignIdFor(CustomField::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name')->nullable();
            $table->unsignedBigInteger('sort_order')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['custom_field_id', 'name']);
        });

        /**
         * Custom Field Values
         */
        Schema::create(config('custom-fields.table_names.custom_field_values'), function (Blueprint $table): void {
            $table->id();

            if (config('custom-fields.tenant_aware', false) && Filament::hasTenancy()) {
                $table->foreignId(config('custom-fields.column_names.tenant_foreign_key'))->nullable()->index();
            }

            $table->morphs('entity');
            $table->foreignIdFor(CustomField::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('string_value')->nullable();
            $table->text('text_value')->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->integer('integer_value')->nullable();
            $table->double('float_value')->nullable();
            $table->date('date_value')->nullable();
            $table->dateTime('datetime_value')->nullable();
            $table->json('json_value')->nullable();

            $table->softDeletes();

            $table->unique(['entity_type', 'entity_id', 'custom_field_id'], 'entity_type_custom_field_value_index_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('custom-fields.table_names.custom_fields'));
        Schema::dropIfExists(config('custom-fields.table_names.custom_field_options'));
        Schema::dropIfExists(config('custom-fields.table_names.custom_field_values'));
    }
};
