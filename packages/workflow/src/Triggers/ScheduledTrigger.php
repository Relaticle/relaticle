<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Triggers;

use Cron\CronExpression;
use Illuminate\Support\Carbon;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Triggers\Contracts\WorkflowTrigger;

class ScheduledTrigger implements WorkflowTrigger
{
    public static function type(): TriggerType
    {
        return TriggerType::TimeBased;
    }

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
            'inactivity' => $this->evaluateInactivity($config, $workflow->tenant_id),
            'date_field' => $this->evaluateDateField($config, $workflow->tenant_id),
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
    private function evaluateInactivity(array $config, ?string $tenantId = null): bool
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

        $query = $modelClass::where('updated_at', '<', $threshold);
        if ($tenantId) {
            $scopeColumn = $this->resolveEntityScopeColumn($modelClass);
            $query->where($scopeColumn, $tenantId);
        }

        return $query->exists();
    }

    /**
     * Evaluate a date-field based trigger.
     *
     * Checks whether any record of the configured model has a date field
     * whose value is within the specified offset from today.
     *
     * @param  array<string, mixed>  $config
     */
    private function evaluateDateField(array $config, ?string $tenantId = null): bool
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

        // Validate field name to prevent injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
            return false;
        }

        // Calculate the target date: if offset_days is -3 (3 days before),
        // we look for records where the date field equals today + 3 days
        // (i.e., the trigger fires 3 days before the date).
        $targetDate = Carbon::today()->addDays(-1 * (int) $offsetDays);

        $query = $modelClass::whereDate($field, $targetDate);
        if ($tenantId) {
            $scopeColumn = $this->resolveEntityScopeColumn($modelClass);
            $query->where($scopeColumn, $tenantId);
        }

        return $query->exists();
    }

    /**
     * Resolve the correct scope column for an entity table.
     */
    private function resolveEntityScopeColumn(string $modelClass, string $default = 'tenant_id'): string
    {
        try {
            $instance = new $modelClass;
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($instance->getTable());

            if (in_array($default, $columns, true)) {
                return $default;
            }

            foreach (['team_id', 'tenant_id', 'organization_id'] as $alt) {
                if (in_array($alt, $columns, true)) {
                    return $alt;
                }
            }
        } catch (\Throwable) {
        }

        return $default;
    }
}
