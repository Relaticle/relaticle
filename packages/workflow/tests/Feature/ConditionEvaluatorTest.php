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

it('uses strict comparison for in operator', function () {
    $evaluator = new ConditionEvaluator();

    $result = $evaluator->evaluate(
        ['field' => 'status', 'operator' => 'in', 'value' => [true, false]],
        ['status' => '1']
    );

    expect($result)->toBeFalse();
});
