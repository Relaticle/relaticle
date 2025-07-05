<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all amount fields across all teams that have type 'number'
        $amountFieldIds = DB::table('custom_fields')
            ->where('code', 'amount')
            ->where('entity_type', 'opportunity')
            ->where('type', 'number')
            ->pluck('id');

        if ($amountFieldIds->isNotEmpty()) {
            // Migrate values from integer_value to float_value for all amount fields
            DB::table('custom_field_values')
                ->whereIn('custom_field_id', $amountFieldIds)
                ->whereNotNull('integer_value')
                ->update([
                    'float_value' => DB::raw('CAST(integer_value AS DECIMAL(15,2))'),
                    'integer_value' => null,
                ]);

            // Update the field type from 'number' to 'currency' for all amount fields
            DB::table('custom_fields')
                ->whereIn('id', $amountFieldIds)
                ->update(['type' => 'currency']);
        }
    }

    /**
     * Determine if this migration should run.
     */
    public function shouldRun(): bool
    {
        return true;
    }
};
