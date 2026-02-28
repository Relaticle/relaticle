<?php

declare(strict_types=1);

use Relaticle\Workflow\Actions\Contracts\WorkflowAction;
use Relaticle\Workflow\Actions\LoopAction;
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
            return [
                'logged' => $config['message'] ?? 'no message',
                'loop_item' => $context['loop']['item'] ?? null,
                'loop_index' => $context['loop']['index'] ?? null,
            ];
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
});

it('iterates over a collection and executes sub-path for each item', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Loop Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 't', 'type' => NodeType::Trigger]);
    $loop = $workflow->nodes()->create([
        'node_id' => 'loop',
        'type' => NodeType::Loop,
        'config' => ['collection' => 'record.items'],
    ]);
    $action = $workflow->nodes()->create([
        'node_id' => 'action',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'processing item'],
    ]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $loop->id]);
    $workflow->edges()->create(['edge_id' => 'e2', 'source_node_id' => $loop->id, 'target_node_id' => $action->id]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, [
        'record' => [
            'items' => [
                ['name' => 'Item A'],
                ['name' => 'Item B'],
                ['name' => 'Item C'],
            ],
        ],
    ]);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);

    // Should have loop step + 3 action steps (one per item)
    $actionSteps = $run->steps->filter(fn ($s) => $s->node->type === NodeType::Action);
    expect($actionSteps)->toHaveCount(3);

    // Each action step should have loop context in output
    $outputs = $actionSteps->pluck('output_data');
    expect($outputs[0]['loop_item'])->toBe(['name' => 'Item A']);
    expect($outputs[0]['loop_index'])->toBe(0);
    expect($outputs[1]['loop_item'])->toBe(['name' => 'Item B']);
    expect($outputs[1]['loop_index'])->toBe(1);
    expect($outputs[2]['loop_item'])->toBe(['name' => 'Item C']);
    expect($outputs[2]['loop_index'])->toBe(2);
});

it('handles empty collection gracefully', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Empty Loop Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 't', 'type' => NodeType::Trigger]);
    $loop = $workflow->nodes()->create([
        'node_id' => 'loop',
        'type' => NodeType::Loop,
        'config' => ['collection' => 'record.items'],
    ]);
    $action = $workflow->nodes()->create([
        'node_id' => 'action',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'processing'],
    ]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $loop->id]);
    $workflow->edges()->create(['edge_id' => 'e2', 'source_node_id' => $loop->id, 'target_node_id' => $action->id]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, ['record' => ['items' => []]]);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);

    // No action steps for empty collection
    $actionSteps = $run->steps->filter(fn ($s) => $s->node->type === NodeType::Action);
    expect($actionSteps)->toHaveCount(0);
});

it('provides loop.item and loop.index variables in context', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Loop Variables Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 't', 'type' => NodeType::Trigger]);
    $loop = $workflow->nodes()->create([
        'node_id' => 'loop',
        'type' => NodeType::Loop,
        'config' => ['collection' => 'record.tags'],
    ]);
    $action = $workflow->nodes()->create([
        'node_id' => 'action',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'config' => ['message' => 'tag'],
    ]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $loop->id]);
    $workflow->edges()->create(['edge_id' => 'e2', 'source_node_id' => $loop->id, 'target_node_id' => $action->id]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, [
        'record' => ['tags' => ['php', 'laravel']],
    ]);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);

    $actionSteps = $run->steps->filter(fn ($s) => $s->node->type === NodeType::Action);
    expect($actionSteps)->toHaveCount(2);
    expect($actionSteps->first()->output_data['loop_item'])->toBe('php');
    expect($actionSteps->last()->output_data['loop_item'])->toBe('laravel');
});

it('returns loop action metadata from execute', function () {
    $action = new LoopAction();

    $result = $action->execute(
        ['collection' => 'record.items'],
        ['record' => ['items' => ['a', 'b', 'c']]],
    );

    expect($result)
        ->toBeArray()
        ->toHaveKey('collection_path', 'record.items')
        ->toHaveKey('item_count', 3);
});

it('has a label and config schema', function () {
    expect(LoopAction::label())
        ->toBeString()
        ->not->toBeEmpty();

    $schema = LoopAction::configSchema();

    expect($schema)
        ->toBeArray()
        ->toHaveKey('collection');

    expect($schema['collection'])
        ->toHaveKey('type', 'string')
        ->toHaveKey('required', true);
});

it('is registered as a built-in action', function () {
    $actions = Workflow::getRegisteredActions();

    expect($actions)->toHaveKey('loop');
    expect($actions['loop'])->toBe(LoopAction::class);
});
