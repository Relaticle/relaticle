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
        if (!isset($condition['field']) || !isset($condition['operator'])) {
            return false;
        }

        $field = $condition['field'];
        $operator = $condition['operator'];
        $fieldValue = data_get($context, $field);

        return match ($operator) {
            'equals' => (string) $fieldValue === (string) ($condition['value'] ?? ''),
            'not_equals' => (string) $fieldValue !== (string) ($condition['value'] ?? ''),
            'contains' => is_string($fieldValue) && isset($condition['value']) && $condition['value'] !== '' && str_contains($fieldValue, (string) $condition['value']),
            'greater_than' => is_numeric($fieldValue) && is_numeric($condition['value'] ?? null) && $fieldValue > $condition['value'],
            'less_than' => is_numeric($fieldValue) && is_numeric($condition['value'] ?? null) && $fieldValue < $condition['value'],
            'is_empty' => $fieldValue === null || $fieldValue === '',
            'is_not_empty' => $fieldValue !== null && $fieldValue !== '',
            'in' => is_array($condition['value'] ?? null) && in_array($fieldValue, $condition['value'], false),
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
            if (empty($conditions)) {
                return false;
            }

            foreach ($conditions as $condition) {
                if (! $this->evaluate($condition, $context)) {
                    return false;
                }
            }

            return true;
        }

        if ($logicalOperator === 'or') {
            if (empty($conditions)) {
                return false;
            }

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
