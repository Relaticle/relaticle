<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Relaticle\Workflow\Actions\DelayAction;
use Relaticle\Workflow\Engine\WorkflowExecutor;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\StepStatus;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Facades\Workflow;
use Relaticle\Workflow\Jobs\ExecuteStepJob;
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

it('pauses workflow and dispatches delayed job for delay node', function () {
    Queue::fake();

    Workflow::registerAction('log_message', get_class(new class extends \Relaticle\Workflow\Actions\BaseAction
    {
        public function execute(array $config, array $context): array
        {
            return ['logged' => true];
        }

        public static function label(): string
        {
            return 'Log';
        }
    }));

    $workflow = WorkflowModel::create([
        'name' => 'Delay Resume Test',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
        'status' => 'live',
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger-1',
        'type' => NodeType::Trigger,
        'position_x' => 0,
        'position_y' => 0,
    ]);

    $delay = $workflow->nodes()->create([
        'node_id' => 'delay-1',
        'type' => NodeType::Delay,
        'config' => ['duration' => 5, 'unit' => 'minutes'],
        'position_x' => 0,
        'position_y' => 100,
    ]);

    $action = $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => [],
        'position_x' => 0,
        'position_y' => 200,
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
    $run = $executor->execute($workflow, []);

    // Run should be paused, not completed
    expect($run->status)->toBe(WorkflowRunStatus::Paused);

    // The delay step should be recorded as completed
    $delayStep = $run->steps->firstWhere('workflow_node_id', $delay->id);
    expect($delayStep)->not->toBeNull();
    expect($delayStep->status)->toBe(StepStatus::Completed);
    expect($delayStep->output_data)->toHaveKey('delayed', true);
    expect($delayStep->output_data)->toHaveKey('delay_seconds', 300);

    // The action after the delay should NOT have been executed yet
    $actionStep = $run->steps->firstWhere('workflow_node_id', $action->id);
    expect($actionStep)->toBeNull();

    // A delayed job should have been dispatched
    Queue::assertPushed(ExecuteStepJob::class, function ($job) {
        return $job->resumeFromNodeId === 'delay-1';
    });
});

it('resumes workflow after delay and completes execution', function () {
    Workflow::registerAction('log_message', get_class(new class extends \Relaticle\Workflow\Actions\BaseAction
    {
        public function execute(array $config, array $context): array
        {
            return ['logged' => true];
        }

        public static function label(): string
        {
            return 'Log';
        }
    }));

    $workflow = WorkflowModel::create([
        'name' => 'Resume Test',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
        'status' => 'live',
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger-1',
        'type' => NodeType::Trigger,
        'position_x' => 0,
        'position_y' => 0,
    ]);

    $delay = $workflow->nodes()->create([
        'node_id' => 'delay-1',
        'type' => NodeType::Delay,
        'config' => ['duration' => 1, 'unit' => 'minutes'],
        'position_x' => 0,
        'position_y' => 100,
    ]);

    $action = $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => [],
        'position_x' => 0,
        'position_y' => 200,
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

    // First execute - should pause at delay
    Queue::fake();
    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, ['test' => true]);
    expect($run->status)->toBe(WorkflowRunStatus::Paused);

    // Now resume - simulating the delayed job executing
    Queue::fake(); // Reset queue fakes
    $run = $executor->resume($run, 'delay-1', ['test' => true]);

    // Should now be completed
    expect($run->status)->toBe(WorkflowRunStatus::Completed);

    // The action after the delay should have executed
    $actionSteps = $run->steps->filter(fn ($s) => $s->node->node_id === 'action-1');
    expect($actionSteps)->toHaveCount(1);
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
