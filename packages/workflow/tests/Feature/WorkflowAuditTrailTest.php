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
use Relaticle\Workflow\Models\WorkflowRun;
use Relaticle\Workflow\Models\WorkflowRunStep;

beforeEach(function () {
    Workflow::registerAction('log_message', get_class(new class implements WorkflowAction
    {
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
});

it('creates a run with correct status after successful execution', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Audit Status Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger',
        'type' => NodeType::Trigger,
    ]);

    $action1 = $workflow->nodes()->create([
        'node_id' => 'action1',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'hello'],
    ]);

    $action2 = $workflow->nodes()->create([
        'node_id' => 'action2',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'world'],
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $action1->id,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e2',
        'source_node_id' => $action1->id,
        'target_node_id' => $action2->id,
    ]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, ['record' => ['name' => 'Test']]);

    expect($run)->toBeInstanceOf(WorkflowRun::class);
    expect($run->status)->toBe(WorkflowRunStatus::Completed);
    expect($run->started_at)->not->toBeNull();
    expect($run->completed_at)->not->toBeNull();
    expect($run->started_at->lte($run->completed_at))->toBeTrue();
    expect($run->error_message)->toBeNull();
});

it('logs steps with correct input and output data', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Audit I/O Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger',
        'type' => NodeType::Trigger,
    ]);

    $action1 = $workflow->nodes()->create([
        'node_id' => 'action1',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'first step'],
    ]);

    $action2 = $workflow->nodes()->create([
        'node_id' => 'action2',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'second step'],
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $action1->id,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e2',
        'source_node_id' => $action1->id,
        'target_node_id' => $action2->id,
    ]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, ['record' => ['name' => 'Acme']]);

    expect($run->steps)->toHaveCount(2);

    $step1 = $run->steps->firstWhere('workflow_node_id', $action1->id);
    $step2 = $run->steps->firstWhere('workflow_node_id', $action2->id);

    // Step 1: input_data should be the resolved config, output_data should be action return
    expect($step1)->not->toBeNull();
    expect($step1->input_data)->toBe(['message' => 'first step']);
    expect($step1->output_data)->toBe(['logged' => 'first step']);

    // Step 2: input_data should be the resolved config, output_data should be action return
    expect($step2)->not->toBeNull();
    expect($step2->input_data)->toBe(['message' => 'second step']);
    expect($step2->output_data)->toBe(['logged' => 'second step']);
});

it('records timing information on completed steps', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Audit Timing Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger',
        'type' => NodeType::Trigger,
    ]);

    $action = $workflow->nodes()->create([
        'node_id' => 'action1',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'timed'],
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $action->id,
    ]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, []);

    expect($run->steps)->toHaveCount(1);

    $step = $run->steps->first();

    expect($step->started_at)->not->toBeNull();
    expect($step->completed_at)->not->toBeNull();
    expect($step->started_at->lte($step->completed_at))->toBeTrue();
});

it('records error message on failed steps and failed run', function () {
    Workflow::registerAction('bomb_action', get_class(new class implements WorkflowAction
    {
        public function execute(array $config, array $context): array
        {
            throw new \RuntimeException('Action exploded: ' . ($config['reason'] ?? 'unknown'));
        }

        public static function label(): string
        {
            return 'Bomb';
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

    $workflow = WorkflowModel::create([
        'name' => 'Audit Error Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger',
        'type' => NodeType::Trigger,
    ]);

    $failNode = $workflow->nodes()->create([
        'node_id' => 'fail_action',
        'type' => NodeType::Action,
        'action_type' => 'bomb_action',
        'config' => ['reason' => 'test failure'],
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $failNode->id,
    ]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, []);

    // Run should be marked as failed with error message
    expect($run->status)->toBe(WorkflowRunStatus::Failed);
    expect($run->error_message)->not->toBeNull();
    expect($run->error_message)->toContain('Action exploded: test failure');
    expect($run->completed_at)->not->toBeNull();
});

it('marks skipped steps with StepStatus Skipped on non-taken branch', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Audit Skipped Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger',
        'type' => NodeType::Trigger,
    ]);

    $condition = $workflow->nodes()->create([
        'node_id' => 'condition',
        'type' => NodeType::Condition,
        'config' => ['field' => 'record.score', 'operator' => 'greater_than', 'value' => 50],
    ]);

    $yesAction = $workflow->nodes()->create([
        'node_id' => 'yes_action',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'high score'],
    ]);

    $noAction = $workflow->nodes()->create([
        'node_id' => 'no_action',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'low score'],
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $condition->id,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e2',
        'source_node_id' => $condition->id,
        'target_node_id' => $yesAction->id,
        'condition_label' => 'yes',
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e3',
        'source_node_id' => $condition->id,
        'target_node_id' => $noAction->id,
        'condition_label' => 'no',
    ]);

    // Execute with score > 50, so "yes" branch should be taken, "no" branch skipped
    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, ['record' => ['score' => 100]]);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);

    $yesStep = $run->steps->firstWhere('workflow_node_id', $yesAction->id);
    $noStep = $run->steps->firstWhere('workflow_node_id', $noAction->id);

    // The "yes" branch step should be completed
    expect($yesStep)->not->toBeNull();
    expect($yesStep->status)->toBe(StepStatus::Completed);

    // The "no" branch step should be skipped
    expect($noStep)->not->toBeNull();
    expect($noStep->status)->toBe(StepStatus::Skipped);
});

it('stores context data on the workflow run', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Audit Context Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger',
        'type' => NodeType::Trigger,
    ]);

    $action = $workflow->nodes()->create([
        'node_id' => 'action1',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'context test'],
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $action->id,
    ]);

    $context = [
        'record' => ['id' => 42, 'name' => 'Acme Corp', 'email' => 'acme@example.com'],
        'user_id' => 7,
        'triggered_by' => 'manual',
    ];

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, $context);

    // Reload from DB to ensure it was persisted correctly
    $run->refresh();

    expect($run->context_data)->toBe($context);
    expect($run->context_data['record']['name'])->toBe('Acme Corp');
    expect($run->context_data['user_id'])->toBe(7);
    expect($run->context_data['triggered_by'])->toBe('manual');
});
