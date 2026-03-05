<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\StepStatus;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Filament\Resources\WorkflowRunResource;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Models\WorkflowRun;
use Relaticle\Workflow\Models\WorkflowRunStep;

it('generates human-readable step labels from node type', function () {
    $workflow = Workflow::create([
        'name' => 'Label Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $triggerNode = $workflow->nodes()->create(['node_id' => 't1', 'type' => NodeType::Trigger]);
    $conditionNode = $workflow->nodes()->create(['node_id' => 'c1', 'type' => NodeType::Condition]);
    $delayNode = $workflow->nodes()->create(['node_id' => 'd1', 'type' => NodeType::Delay]);
    $actionNode = $workflow->nodes()->create([
        'node_id' => 'a1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
    ]);

    $run = $workflow->runs()->create([
        'status' => WorkflowRunStatus::Completed,
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    $run->steps()->create([
        'workflow_node_id' => $conditionNode->id,
        'status' => StepStatus::Completed,
    ]);

    $run->steps()->create([
        'workflow_node_id' => $delayNode->id,
        'status' => StepStatus::Completed,
    ]);

    $run->steps()->create([
        'workflow_node_id' => $actionNode->id,
        'status' => StepStatus::Completed,
    ]);

    $run->load('steps.node');

    // Use reflection to test the private getStepLabel method
    $method = new ReflectionMethod(WorkflowRunResource::class, 'getStepLabel');
    $method->setAccessible(true);

    $steps = $run->steps;

    expect($method->invoke(null, $steps[0]))->toBe('If / Else');
    expect($method->invoke(null, $steps[1]))->toBe('Delay');
    expect($method->invoke(null, $steps[2]))->toBe('Send Email');
});

it('formats action_type slug as readable label', function () {
    $method = new ReflectionMethod(WorkflowRunResource::class, 'getActionLabel');
    $method->setAccessible(true);

    expect($method->invoke(null, 'send_email'))->toBe('Send Email');
    expect($method->invoke(null, 'create_record'))->toBe('Create Record');
    expect($method->invoke(null, 'prompt_completion'))->toBe('Prompt Completion');
    expect($method->invoke(null, null))->toBe('Action');
});

it('formats JSON data for display', function () {
    $method = new ReflectionMethod(WorkflowRunResource::class, 'formatJsonField');
    $method->setAccessible(true);

    $result = $method->invoke(null, ['key' => 'value']);
    expect($result)->toContain('"key": "value"');

    $empty = $method->invoke(null, []);
    expect($empty)->toBe('(none)');

    $null = $method->invoke(null, null);
    expect($null)->toBe('(none)');
});

it('calculates duration from duration_ms field', function () {
    $workflow = Workflow::create([
        'name' => 'Duration Display',
        'trigger_type' => TriggerType::Manual,
    ]);

    $node = $workflow->nodes()->create([
        'node_id' => 'a1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
    ]);

    $run = $workflow->runs()->create([
        'status' => WorkflowRunStatus::Completed,
        'started_at' => now()->subSeconds(2),
        'completed_at' => now(),
    ]);

    $step = $run->steps()->create([
        'workflow_node_id' => $node->id,
        'status' => StepStatus::Completed,
        'duration_ms' => 150,
        'started_at' => now()->subMilliseconds(150),
        'completed_at' => now(),
    ]);

    expect($step->duration_ms)->toBe(150);

    $stepSlow = $run->steps()->create([
        'workflow_node_id' => $node->id,
        'status' => StepStatus::Completed,
        'duration_ms' => 2500,
        'started_at' => now()->subMilliseconds(2500),
        'completed_at' => now(),
    ]);

    expect($stepSlow->duration_ms)->toBe(2500);
});

it('stores and retrieves input_data and output_data as arrays', function () {
    $workflow = Workflow::create([
        'name' => 'IO Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $node = $workflow->nodes()->create([
        'node_id' => 'a1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
    ]);

    $run = $workflow->runs()->create([
        'status' => WorkflowRunStatus::Completed,
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    $inputData = ['to' => 'test@example.com', 'subject' => 'Hello'];
    $outputData = ['sent' => true, 'message_id' => 'abc-123'];

    $step = $run->steps()->create([
        'workflow_node_id' => $node->id,
        'status' => StepStatus::Completed,
        'input_data' => $inputData,
        'output_data' => $outputData,
    ]);

    $step->refresh();

    expect($step->input_data)->toBe($inputData);
    expect($step->output_data)->toBe($outputData);
});

it('stores context_data on workflow run', function () {
    $workflow = Workflow::create([
        'name' => 'Context Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $contextData = ['record' => ['id' => '123', 'name' => 'Test'], 'event' => 'created'];

    $run = $workflow->runs()->create([
        'status' => WorkflowRunStatus::Completed,
        'started_at' => now(),
        'completed_at' => now(),
        'context_data' => $contextData,
    ]);

    $run->refresh();

    expect($run->context_data)->toBe($contextData);
});
