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
    Workflow::registerAction('log_message', get_class(new class implements WorkflowAction {
        public function execute(array $config, array $context): array
        {
            return ['logged' => $config['message'] ?? 'no message'];
        }
        public static function label(): string { return 'Log Message'; }
        public static function configSchema(): array { return []; }
    }));
});

it('executes a linear workflow (trigger -> action -> action)', function () {
    $workflow = createLinearWorkflow();

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, ['record' => ['name' => 'Acme']]);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);
    expect($run->steps)->toHaveCount(2);
    expect($run->steps->every(fn ($step) => $step->status === StepStatus::Completed))->toBeTrue();
});

it('executes branching workflow and follows "yes" path', function () {
    $workflow = createBranchingWorkflow();

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, [
        'record' => ['amount' => 5000],
    ]);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);

    $completedSteps = $run->steps->where('status', StepStatus::Completed);
    $skippedSteps = $run->steps->where('status', StepStatus::Skipped);

    expect($completedSteps)->not->toBeEmpty();
    expect($skippedSteps)->not->toBeEmpty();
});

it('marks run as failed when an action throws exception', function () {
    Workflow::registerAction('failing_action', get_class(new class implements WorkflowAction {
        public function execute(array $config, array $context): array
        {
            throw new \RuntimeException('Something broke');
        }
        public static function label(): string { return 'Failing'; }
        public static function configSchema(): array { return []; }
    }));

    $workflow = WorkflowModel::create([
        'name' => 'Failing Workflow',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger',
        'type' => NodeType::Trigger,
    ]);

    $failNode = $workflow->nodes()->create([
        'node_id' => 'fail',
        'type' => NodeType::Action,
        'action_type' => 'failing_action',
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $failNode->id,
    ]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, []);

    expect($run->status)->toBe(WorkflowRunStatus::Failed);
    expect($run->error_message)->toContain('Something broke');
});

it('stops execution at a stop node', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Stop Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 't', 'type' => NodeType::Trigger]);
    $action = $workflow->nodes()->create(['node_id' => 'a', 'type' => NodeType::Action, 'action_type' => 'log_message', 'config' => ['message' => 'hi']]);
    $stop = $workflow->nodes()->create(['node_id' => 's', 'type' => NodeType::Stop]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $action->id]);
    $workflow->edges()->create(['edge_id' => 'e2', 'source_node_id' => $action->id, 'target_node_id' => $stop->id]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, []);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);
});

// Helper functions
function createLinearWorkflow(): WorkflowModel
{
    $workflow = WorkflowModel::create([
        'name' => 'Linear Workflow',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);
    $action1 = $workflow->nodes()->create(['node_id' => 'action1', 'type' => NodeType::Action, 'action_type' => 'log_message', 'config' => ['message' => 'step 1']]);
    $action2 = $workflow->nodes()->create(['node_id' => 'action2', 'type' => NodeType::Action, 'action_type' => 'log_message', 'config' => ['message' => 'step 2']]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $action1->id]);
    $workflow->edges()->create(['edge_id' => 'e2', 'source_node_id' => $action1->id, 'target_node_id' => $action2->id]);

    return $workflow;
}

function createBranchingWorkflow(): WorkflowModel
{
    $workflow = WorkflowModel::create([
        'name' => 'Branching Workflow',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);
    $condition = $workflow->nodes()->create([
        'node_id' => 'condition',
        'type' => NodeType::Condition,
        'config' => ['field' => 'record.amount', 'operator' => 'greater_than', 'value' => 1000],
    ]);
    $yesAction = $workflow->nodes()->create(['node_id' => 'yes_action', 'type' => NodeType::Action, 'action_type' => 'log_message', 'config' => ['message' => 'big deal']]);
    $noAction = $workflow->nodes()->create(['node_id' => 'no_action', 'type' => NodeType::Action, 'action_type' => 'log_message', 'config' => ['message' => 'small deal']]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $condition->id]);
    $workflow->edges()->create(['edge_id' => 'e2', 'source_node_id' => $condition->id, 'target_node_id' => $yesAction->id, 'condition_label' => 'yes']);
    $workflow->edges()->create(['edge_id' => 'e3', 'source_node_id' => $condition->id, 'target_node_id' => $noAction->id, 'condition_label' => 'no']);

    return $workflow;
}
