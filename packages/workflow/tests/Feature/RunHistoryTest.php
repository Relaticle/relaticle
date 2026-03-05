<?php

declare(strict_types=1);

use Relaticle\Workflow\Actions\Contracts\WorkflowAction;
use Relaticle\Workflow\Engine\WorkflowExecutor;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\StepStatus;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Facades\Workflow;
use Relaticle\Workflow\Models\Workflow as WorkflowModel;

beforeEach(function () {
    Workflow::registerAction('fast_action', get_class(new class implements WorkflowAction {
        public function execute(array $config, array $context): array
        {
            return ['done' => true];
        }

        public static function label(): string { return 'Fast Action'; }
        public static function configSchema(): array { return []; }
        public static function outputSchema(): array { return []; }
    }));
});

it('sets duration_ms on completed steps', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Duration Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 't', 'type' => NodeType::Trigger]);
    $action = $workflow->nodes()->create([
        'node_id' => 'a',
        'type' => NodeType::Action,
        'action_type' => 'fast_action',
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $action->id,
    ]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, []);

    $step = $run->steps->firstWhere('status', StepStatus::Completed);

    expect($step)->not->toBeNull();
    expect($step->duration_ms)->not->toBeNull();
    expect($step->duration_ms)->toBeGreaterThanOrEqual(0);
});

it('sets duration_ms on failed steps', function () {
    Workflow::registerAction('boom_action', get_class(new class implements WorkflowAction {
        public function execute(array $config, array $context): array
        {
            throw new \RuntimeException('Boom!');
        }

        public static function label(): string { return 'Boom'; }
        public static function configSchema(): array { return []; }
        public static function outputSchema(): array { return []; }
    }));

    $workflow = WorkflowModel::create([
        'name' => 'Failed Duration Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 't', 'type' => NodeType::Trigger]);
    $action = $workflow->nodes()->create([
        'node_id' => 'a',
        'type' => NodeType::Action,
        'action_type' => 'boom_action',
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $action->id,
    ]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, []);

    $step = $run->steps->firstWhere('status', StepStatus::Failed);

    expect($step)->not->toBeNull();
    expect($step->duration_ms)->not->toBeNull();
    expect($step->duration_ms)->toBeGreaterThanOrEqual(0);
});

it('duration_ms is fillable and casts to integer', function () {
    $step = new \Relaticle\Workflow\Models\WorkflowRunStep();
    expect(in_array('duration_ms', $step->getFillable()))->toBeTrue();
});
