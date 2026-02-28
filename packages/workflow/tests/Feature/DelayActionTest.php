<?php

declare(strict_types=1);

use Relaticle\Workflow\Actions\DelayAction;
use Relaticle\Workflow\Engine\WorkflowExecutor;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\StepStatus;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Facades\Workflow;
use Relaticle\Workflow\Models\Workflow as WorkflowModel;

it('returns delay configuration with correct seconds for minutes', function () {
    $action = new DelayAction();

    $result = $action->execute(
        ['duration' => 60, 'unit' => 'minutes'],
        ['record' => ['name' => 'Test']],
    );

    expect($result)
        ->toBeArray()
        ->toHaveKey('delayed', true)
        ->toHaveKey('duration', 60)
        ->toHaveKey('unit', 'minutes')
        ->toHaveKey('delay_seconds', 3600);
});

it('returns delay configuration with correct seconds for hours', function () {
    $action = new DelayAction();

    $result = $action->execute(
        ['duration' => 2, 'unit' => 'hours'],
        [],
    );

    expect($result)
        ->toHaveKey('delayed', true)
        ->toHaveKey('duration', 2)
        ->toHaveKey('unit', 'hours')
        ->toHaveKey('delay_seconds', 7200);
});

it('returns delay configuration with correct seconds for days', function () {
    $action = new DelayAction();

    $result = $action->execute(
        ['duration' => 1, 'unit' => 'days'],
        [],
    );

    expect($result)
        ->toHaveKey('delayed', true)
        ->toHaveKey('duration', 1)
        ->toHaveKey('unit', 'days')
        ->toHaveKey('delay_seconds', 86400);
});

it('defaults to zero duration and minutes unit when config is empty', function () {
    $action = new DelayAction();

    $result = $action->execute([], []);

    expect($result)
        ->toHaveKey('delayed', true)
        ->toHaveKey('duration', 0)
        ->toHaveKey('unit', 'minutes')
        ->toHaveKey('delay_seconds', 0);
});

it('executes a workflow with a delay node and logs the delay step', function () {
    Workflow::registerAction('log_message', get_class(new class implements \Relaticle\Workflow\Actions\Contracts\WorkflowAction {
        public function execute(array $config, array $context): array
        {
            return ['logged' => $config['message'] ?? 'no message'];
        }

        public static function label(): string
        {
            return 'Log Message';
        }

        public static function configSchema(): array
        {
            return [];
        }
    }));

    $workflow = WorkflowModel::create([
        'name' => 'Delay Workflow',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger',
        'type' => NodeType::Trigger,
    ]);

    $delay = $workflow->nodes()->create([
        'node_id' => 'delay',
        'type' => NodeType::Delay,
        'action_type' => 'delay',
        'config' => ['duration' => 5, 'unit' => 'minutes'],
    ]);

    $action = $workflow->nodes()->create([
        'node_id' => 'action',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'after delay'],
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $delay->id,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e2',
        'source_node_id' => $delay->id,
        'target_node_id' => $action->id,
    ]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, ['record' => ['name' => 'Test']]);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);
    expect($run->steps)->toHaveCount(2);

    // The delay step should be completed and have output with delay info
    $delayStep = $run->steps->firstWhere('workflow_node_id', $delay->id);
    expect($delayStep)->not->toBeNull();
    expect($delayStep->status)->toBe(StepStatus::Completed);
    expect($delayStep->output_data)->toHaveKey('delayed', true);
    expect($delayStep->output_data)->toHaveKey('delay_seconds', 300);

    // The action step should also be completed
    $actionStep = $run->steps->firstWhere('workflow_node_id', $action->id);
    expect($actionStep)->not->toBeNull();
    expect($actionStep->status)->toBe(StepStatus::Completed);
});

it('has a label and config schema', function () {
    expect(DelayAction::label())
        ->toBeString()
        ->not->toBeEmpty();

    $schema = DelayAction::configSchema();

    expect($schema)
        ->toBeArray()
        ->toHaveKey('duration')
        ->toHaveKey('unit');

    expect($schema['duration'])
        ->toHaveKey('type', 'integer')
        ->toHaveKey('required', true);

    expect($schema['unit'])
        ->toHaveKey('type', 'select')
        ->toHaveKey('options');
});

it('is registered as a built-in action', function () {
    $actions = Workflow::getRegisteredActions();

    expect($actions)->toHaveKey('delay');
    expect($actions['delay'])->toBe(DelayAction::class);
});
