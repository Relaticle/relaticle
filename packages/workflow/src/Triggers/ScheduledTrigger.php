<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Triggers;

use Cron\CronExpression;
use Illuminate\Support\Carbon;
use Relaticle\Workflow\Models\Workflow;

class ScheduledTrigger
{
    /**
     * Evaluate whether a time-based workflow should fire right now.
     *
     * Supports the following schedule types:
     *  - cron: fires when the cron expression is due
     *  - inactivity: fires when any record of the configured model has not been updated within the threshold
     *  - date_field: fires when a date field on a model is within the configured offset (future use)
     */
    public function evaluate(Workflow $workflow): bool
    {
        $config = $workflow->trigger_config ?? [];
        $scheduleType = $config['schedule_type'] ?? null;

        return match ($scheduleType) {
            'cron' => $this->evaluateCron($config),
            'inactivity' => $this->evaluateInactivity($config),
            'date_field' => $this->evaluateDateField($config),
            default => false,
        };
    }

    /**
     * Check if a cron expression is currently due.
     *
     * @param  array<string, mixed>  $config
     */
    private function evaluateCron(array $config): bool
    {
        $cronExpr = $config['cron'] ?? null;

        if ($cronExpr === null) {
            return false;
        }

        $expression = new CronExpression($cronExpr);

        return $expression->isDue(Carbon::now());
    }

    /**
     * Check if any records of the configured model have been inactive
     * (not updated) for longer than the specified number of days.
     *
     * @param  array<string, mixed>  $config
     */
    private function evaluateInactivity(array $config): bool
    {
        $modelClass = $config['model'] ?? null;
        $inactiveDays = $config['inactive_days'] ?? null;

        if ($modelClass === null || $inactiveDays === null) {
            return false;
        }

        if (! class_exists($modelClass)) {
            return false;
        }

        $threshold = Carbon::now()->subDays((int) $inactiveDays);

        return $modelClass::where('updated_at', '<', $threshold)->exists();
    }

    /**
     * Evaluate a date-field based trigger.
     *
     * Checks whether any record of the configured model has a date field
     * whose value is within the specified offset from today.
     *
     * @param  array<string, mixed>  $config
     */
    private function evaluateDateField(array $config): bool
    {
        $modelClass = $config['model'] ?? null;
        $field = $config['field'] ?? null;
        $offsetDays = $config['offset_days'] ?? null;

        if ($modelClass === null || $field === null || $offsetDays === null) {
            return false;
        }

        if (! class_exists($modelClass)) {
            return false;
        }

        // Calculate the target date: if offset_days is -3 (3 days before),
        // we look for records where the date field equals today + 3 days
        // (i.e., the trigger fires 3 days before the date).
        $targetDate = Carbon::today()->addDays(-1 * (int) $offsetDays);

        return $modelClass::whereDate($field, $targetDate)->exists();
    }
}
