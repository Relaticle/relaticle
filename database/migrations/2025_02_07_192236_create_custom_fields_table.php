<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\Utils;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Custom Field Sections
         */
        Schema::create(config('custom-fields.table_names.custom_field_sections'), function (Blueprint $table): void {
            $uniqueColumns = ['entity_type', 'code'];

            $table->id();

            if (Utils::isTenantEnabled()) {
                $table->foreignId(config('custom-fields.column_names.tenant_foreign_key'))->nullable()->index();
                $uniqueColumns[] = config('custom-fields.column_names.tenant_foreign_key');
            }

            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->string('entity_type');
            $table->unsignedBigInteger('sort_order')->nullable();

            $table->string('description')->nullable();

            $table->boolean('active')->default(true);
            $table->boolean('system_defined')->default(false);

            $table->json('settings')->nullable();

            $table->unique($uniqueColumns);

            $table->timestamps();
        });

        /**
         * Custom Fields
         */
        Schema::create(config('custom-fields.table_names.custom_fields'), function (Blueprint $table): void {
            $uniqueColumns = ['code', 'entity_type'];

            $table->id();

            $table->unsignedBigInteger('custom_field_section_id')->nullable();
            $table->string('width')->nullable();

            if (Utils::isTenantEnabled()) {
                $table->foreignId(config('custom-fields.column_names.tenant_foreign_key'))->nullable()->index();
                $uniqueColumns[] = config('custom-fields.column_names.tenant_foreign_key');
            }

            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->string('lookup_type')->nullable();
            $table->string('entity_type');
            $table->unsignedBigInteger('sort_order')->nullable();
            $table->json('validation_rules')->nullable();

            $table->boolean('active')->default(true);
            $table->boolean('system_defined')->default(false);

            $table->json('settings')->nullable();

            $table->unique($uniqueColumns);

            $table->timestamps();
        });

        /**
         * Custom Field Options
         */
        Schema::create(config('custom-fields.table_names.custom_field_options'), function (Blueprint $table): void {
            $uniqueColumns = ['custom_field_id', 'name'];

            $table->id();

            if (Utils::isTenantEnabled()) {
                $table->foreignId(config('custom-fields.column_names.tenant_foreign_key'))->nullable()->index();
                $uniqueColumns[] = config('custom-fields.column_names.tenant_foreign_key');
            }

            $table->foreignIdFor(CustomField::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name')->nullable();
            $table->unsignedBigInteger('sort_order')->nullable();
            $table->json('settings')->nullable();

            $table->timestamps();

            $table->unique($uniqueColumns);
        });

        /**
         * Custom Field Values
         */
        Schema::create(config('custom-fields.table_names.custom_field_values'), function (Blueprint $table): void {
            $uniqueColumns = ['entity_type', 'entity_id', 'custom_field_id'];

            $table->id();

            if (Utils::isTenantEnabled()) {
                $table->foreignId(config('custom-fields.column_names.tenant_foreign_key'))->nullable()->index();
                $uniqueColumns[] = config('custom-fields.column_names.tenant_foreign_key');
            }

            $table->morphs('entity');
            $table->foreignIdFor(CustomField::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->text('string_value')->nullable();
            $table->longText('text_value')->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->integer('integer_value')->nullable();
            $table->double('float_value')->nullable();
            $table->date('date_value')->nullable();
            $table->dateTime('datetime_value')->nullable();
            $table->json('json_value')->nullable();

            $table->unique($uniqueColumns, 'custom_field_values_entity_type_unique');
            $table->index(['entity_id', 'custom_field_id'], 'custom_field_values_entity_id_custom_field_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('custom-fields.table_names.custom_field_values'));
        Schema::dropIfExists(config('custom-fields.table_names.custom_field_options'));
        Schema::dropIfExists(config('custom-fields.table_names.custom_fields'));
        Schema::dropIfExists(config('custom-fields.table_names.custom_field_sections'));
    }
};
