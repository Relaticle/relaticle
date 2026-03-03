<?php

declare(strict_types=1);

use Relaticle\Workflow\Engine\ConditionEvaluator;

it('evaluates "equals" condition', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['status' => 'active']];

    expect($evaluator->evaluate([
        'field' => 'record.status',
        'operator' => 'equals',
        'value' => 'active',
    ], $context))->toBeTrue();

    expect($evaluator->evaluate([
        'field' => 'record.status',
        'operator' => 'equals',
        'value' => 'inactive',
    ], $context))->toBeFalse();
});

it('evaluates "not_equals" condition', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['status' => 'active']];

    expect($evaluator->evaluate([
        'field' => 'record.status',
        'operator' => 'not_equals',
        'value' => 'inactive',
    ], $context))->toBeTrue();
});

it('evaluates "contains" condition', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['email' => 'john@acme.com']];

    expect($evaluator->evaluate([
        'field' => 'record.email',
        'operator' => 'contains',
        'value' => 'acme',
    ], $context))->toBeTrue();
});

it('evaluates "greater_than" and "less_than" conditions', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['amount' => 5000]];

    expect($evaluator->evaluate([
        'field' => 'record.amount',
        'operator' => 'greater_than',
        'value' => 1000,
    ], $context))->toBeTrue();

    expect($evaluator->evaluate([
        'field' => 'record.amount',
        'operator' => 'less_than',
        'value' => 10000,
    ], $context))->toBeTrue();
});

it('evaluates "is_empty" and "is_not_empty" conditions', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['name' => 'Acme', 'notes' => '']];

    expect($evaluator->evaluate([
        'field' => 'record.notes',
        'operator' => 'is_empty',
    ], $context))->toBeTrue();

    expect($evaluator->evaluate([
        'field' => 'record.name',
        'operator' => 'is_not_empty',
    ], $context))->toBeTrue();
});

it('evaluates "in" condition', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['status' => 'active']];

    expect($evaluator->evaluate([
        'field' => 'record.status',
        'operator' => 'in',
        'value' => ['active', 'pending'],
    ], $context))->toBeTrue();
});

it('evaluates compound AND conditions', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['status' => 'active', 'amount' => 5000]];

    expect($evaluator->evaluateGroup([
        'operator' => 'and',
        'conditions' => [
            ['field' => 'record.status', 'operator' => 'equals', 'value' => 'active'],
            ['field' => 'record.amount', 'operator' => 'greater_than', 'value' => 1000],
        ],
    ], $context))->toBeTrue();

    expect($evaluator->evaluateGroup([
        'operator' => 'and',
        'conditions' => [
            ['field' => 'record.status', 'operator' => 'equals', 'value' => 'active'],
            ['field' => 'record.amount', 'operator' => 'greater_than', 'value' => 9999],
        ],
    ], $context))->toBeFalse();
});

it('evaluates compound OR conditions', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['status' => 'inactive', 'amount' => 5000]];

    expect($evaluator->evaluateGroup([
        'operator' => 'or',
        'conditions' => [
            ['field' => 'record.status', 'operator' => 'equals', 'value' => 'active'],
            ['field' => 'record.amount', 'operator' => 'greater_than', 'value' => 1000],
        ],
    ], $context))->toBeTrue();
});

it('uses strict string comparison for equals', function () {
    $evaluator = new ConditionEvaluator();

    // 0 should NOT equal empty string (PHP loose comparison gotcha)
    expect($evaluator->evaluate(
        ['field' => 'value', 'operator' => 'equals', 'value' => ''],
        ['value' => 0]
    ))->toBeFalse();

    // 0 should NOT equal null
    expect($evaluator->evaluate(
        ['field' => 'value', 'operator' => 'equals', 'value' => null],
        ['value' => 0]
    ))->toBeFalse();

    // String "1" should equal string "1"
    expect($evaluator->evaluate(
        ['field' => 'value', 'operator' => 'equals', 'value' => '1'],
        ['value' => '1']
    ))->toBeTrue();

    // Numeric 1 should equal string "1" (reasonable coercion via string cast)
    expect($evaluator->evaluate(
        ['field' => 'value', 'operator' => 'equals', 'value' => '1'],
        ['value' => 1]
    ))->toBeTrue();
});

it('treats 0 as not empty for is_empty operator', function () {
    $evaluator = new ConditionEvaluator();

    expect($evaluator->evaluate(
        ['field' => 'amount', 'operator' => 'is_empty'],
        ['amount' => 0]
    ))->toBeFalse();

    expect($evaluator->evaluate(
        ['field' => 'amount', 'operator' => 'is_empty'],
        ['amount' => '']
    ))->toBeTrue();

    expect($evaluator->evaluate(
        ['field' => 'amount', 'operator' => 'is_empty'],
        ['amount' => null]
    ))->toBeTrue();
});

it('uses loose comparison for in operator to handle type coercion', function () {
    $evaluator = new ConditionEvaluator();

    // Integer value should match string array element (loose comparison)
    $result = $evaluator->evaluate(
        ['field' => 'age', 'operator' => 'in', 'value' => ['25', '30']],
        ['age' => 25]
    );

    expect($result)->toBeTrue();

    // Non-matching value should still return false
    $result2 = $evaluator->evaluate(
        ['field' => 'status', 'operator' => 'in', 'value' => ['active', 'pending']],
        ['status' => 'closed']
    );

    expect($result2)->toBeFalse();
});
