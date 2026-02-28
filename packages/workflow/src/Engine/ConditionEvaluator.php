<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Engine;

use InvalidArgumentException;

class ConditionEvaluator
{
    /**
     * Evaluate a single condition against the given context.
     *
     * Supported operators: equals, not_equals, contains, greater_than,
     * less_than, is_empty, is_not_empty, in.
     *
     * @param  array{field: string, operator: string, value?: mixed}  $condition
     * @param  array<string, mixed>  $context
     */
    public function evaluate(array $condition, array $context): bool
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $fieldValue = data_get($context, $field);

        return match ($operator) {
            'equals' => $fieldValue == $condition['value'],
            'not_equals' => $fieldValue != $condition['value'],
            'contains' => is_string($fieldValue) && str_contains($fieldValue, (string) $condition['value']),
            'greater_than' => $fieldValue > $condition['value'],
            'less_than' => $fieldValue < $condition['value'],
            'is_empty' => empty($fieldValue),
            'is_not_empty' => ! empty($fieldValue),
            'in' => is_array($condition['value']) && in_array($fieldValue, $condition['value']),
            default => throw new InvalidArgumentException("Unsupported operator: {$operator}"),
        };
    }

    /**
     * Evaluate a group of conditions with AND/OR logic.
     *
     * @param  array{operator: string, conditions: array<int, array{field: string, operator: string, value?: mixed}>}  $group
     * @param  array<string, mixed>  $context
     */
    public function evaluateGroup(array $group, array $context): bool
    {
        $logicalOperator = $group['operator'];
        $conditions = $group['conditions'];

        if ($logicalOperator === 'and') {
            foreach ($conditions as $condition) {
                if (! $this->evaluate($condition, $context)) {
                    return false;
                }
            }

            return true;
        }

        if ($logicalOperator === 'or') {
            foreach ($conditions as $condition) {
                if ($this->evaluate($condition, $context)) {
                    return true;
                }
            }

            return false;
        }

        throw new InvalidArgumentException("Unsupported logical operator: {$logicalOperator}");
    }
}
