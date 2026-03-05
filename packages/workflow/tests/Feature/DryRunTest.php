<?php

declare(strict_types=1);

use Relaticle\Workflow\Actions\Contracts\WorkflowAction;
use Relaticle\Workflow\Engine\WorkflowExecutor;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\StepStatus;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Facades\Workflow;
use Relaticle\Workflow\Models\Workflow as WorkflowModel;

beforeEach(function () {
    // Register a safe action (no side effects)
    Workflow::registerAction('log_message', get_class(new class implements WorkflowAction {
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

        public static function outputSchema(): array
        {
            return [];
        }
    }));

    // Register a side-effect action
    Workflow::registerAction('dangerous_action', get_class(new class implements WorkflowAction {
        public function execute(array $config, array $context): array
        {
            // This would be dangerous in production
            return ['executed' => true, 'danger' => 'real side effect happened'];
        }

        public static function label(): string
        {
            return 'Dangerous Action';
        }

        public static function hasSideEffects(): bool
        {
            return true;
        }

        public static function configSchema(): array
        {
            return [];
        }

        public static function outputSchema(): array
        {
            return [];
        }
    }));
});

it('executes safe actions normally in dry run', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Dry Run Safe',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 't', 'type' => NodeType::Trigger]);
    $action = $workflow->nodes()->create([
        'node_id' => 'a1',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'hello'],
    ]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $action->id]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->dryRun($workflow, []);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);

    $step = $run->steps->first(fn ($s) => $s->node->type === NodeType::Action);
    expect($step->status)->toBe(StepStatus::Completed);
    expect($step->output_data['logged'])->toBe('hello');
    expect($step->output_data)->not->toHaveKey('_dry_run');
});

it('executes all actions including side-effect actions in test run', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Test Run E2E',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 't', 'type' => NodeType::Trigger]);
    $action = $workflow->nodes()->create([
        'node_id' => 'a1',
        'type' => NodeType::Action,
        'action_type' => 'dangerous_action',
        'config' => [],
    ]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $action->id]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->dryRun($workflow, []);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);

    $step = $run->steps->first(fn ($s) => $s->node->type === NodeType::Action);
    expect($step->status)->toBe(StepStatus::Completed);
    // Test run executes everything for real — no simulation
    expect($step->output_data['executed'])->toBeTrue();
    expect($step->output_data['danger'])->toBe('real side effect happened');
    expect($step->output_data)->not->toHaveKey('_dry_run');
});

it('continues through delay nodes in dry run', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Dry Run Delay',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 't', 'type' => NodeType::Trigger]);
    $delay = $workflow->nodes()->create([
        'node_id' => 'd1',
        'type' => NodeType::Delay,
        'config' => ['duration' => 60, 'unit' => 'minutes'],
    ]);
    $action = $workflow->nodes()->create([
        'node_id' => 'a1',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'after delay'],
    ]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $delay->id]);
    $workflow->edges()->create(['edge_id' => 'e2', 'source_node_id' => $delay->id, 'target_node_id' => $action->id]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->dryRun($workflow, []);

    // Should complete (not pause)
    expect($run->status)->toBe(WorkflowRunStatus::Completed);

    // Should have both delay and action steps
    expect($run->steps)->toHaveCount(2);

    $actionStep = $run->steps->first(fn ($s) => $s->node->type === NodeType::Action);
    expect($actionStep->output_data['logged'])->toBe('after delay');
});

it('returns execution trace from dry run', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Dry Run Trace',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 't', 'type' => NodeType::Trigger]);
    $safe = $workflow->nodes()->create([
        'node_id' => 'a1',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'step 1'],
    ]);
    $dangerous = $workflow->nodes()->create([
        'node_id' => 'a2',
        'type' => NodeType::Action,
        'action_type' => 'dangerous_action',
        'config' => [],
    ]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $safe->id]);
    $workflow->edges()->create(['edge_id' => 'e2', 'source_node_id' => $safe->id, 'target_node_id' => $dangerous->id]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->dryRun($workflow, []);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);
    expect($run->steps)->toHaveCount(2);

    // First step: executed normally
    $firstStep = $run->steps->sortBy('created_at')->first();
    expect($firstStep->output_data['logged'])->toBe('step 1');

    // Second step: skipped (side effect)
    $secondStep = $run->steps->sortBy('created_at')->last();
    expect($secondStep->output_data['_dry_run'])->toBeTrue();
});
