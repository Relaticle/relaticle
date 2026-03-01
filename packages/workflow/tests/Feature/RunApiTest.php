<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\StepStatus;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Models\WorkflowRun;

it('lists runs for a workflow', function () {
    $workflow = Workflow::create([
        'name' => 'Run List Test',
        'trigger_type' => TriggerType::Manual,
        'status' => 'draft',
    ]);

    $workflow->runs()->create([
        'status' => WorkflowRunStatus::Completed,
        'started_at' => now()->subMinutes(10),
        'completed_at' => now()->subMinutes(5),
    ]);

    $workflow->runs()->create([
        'status' => WorkflowRunStatus::Failed,
        'started_at' => now()->subMinutes(3),
        'error_message' => 'Something went wrong',
    ]);

    $response = $this->getJson("/workflow/api/workflows/{$workflow->id}/runs");

    $response->assertOk();
    $response->assertJsonCount(2, 'runs');
    $response->assertJsonStructure([
        'runs' => [
            '*' => ['id', 'status', 'started_at', 'completed_at', 'error_message'],
        ],
    ]);
});

it('returns runs ordered by started_at descending', function () {
    $workflow = Workflow::create([
        'name' => 'Run Order Test',
        'trigger_type' => TriggerType::Manual,
        'status' => 'draft',
    ]);

    $olderRun = $workflow->runs()->create([
        'status' => WorkflowRunStatus::Completed,
        'started_at' => now()->subMinutes(10),
        'completed_at' => now()->subMinutes(5),
    ]);

    $newerRun = $workflow->runs()->create([
        'status' => WorkflowRunStatus::Running,
        'started_at' => now()->subMinute(),
    ]);

    $response = $this->getJson("/workflow/api/workflows/{$workflow->id}/runs");

    $response->assertOk();
    $runs = $response->json('runs');
    expect($runs[0]['id'])->toBe($newerRun->id);
    expect($runs[1]['id'])->toBe($olderRun->id);
});

it('shows a single run with steps', function () {
    $workflow = Workflow::create([
        'name' => 'Run Detail Test',
        'trigger_type' => TriggerType::Manual,
        'status' => 'draft',
    ]);

    $node = $workflow->nodes()->create([
        'node_id' => 'trigger-1',
        'type' => NodeType::Trigger,
        'position_x' => 0,
        'position_y' => 0,
    ]);

    $run = $workflow->runs()->create([
        'status' => WorkflowRunStatus::Completed,
        'started_at' => now()->subMinutes(5),
        'completed_at' => now(),
        'context_data' => ['key' => 'value'],
    ]);

    $run->steps()->create([
        'workflow_node_id' => $node->id,
        'status' => StepStatus::Completed,
        'input_data' => ['input' => 'test'],
        'output_data' => ['output' => 'result'],
        'started_at' => now()->subMinutes(4),
        'completed_at' => now()->subMinutes(3),
    ]);

    $response = $this->getJson("/workflow/api/workflow-runs/{$run->id}");

    $response->assertOk();
    $response->assertJsonStructure([
        'run' => [
            'id',
            'status',
            'started_at',
            'completed_at',
            'error_message',
            'context_data',
            'steps' => [
                '*' => [
                    'id',
                    'node_id',
                    'status',
                    'input_data',
                    'output_data',
                    'error_message',
                    'started_at',
                    'completed_at',
                ],
            ],
        ],
    ]);

    $data = $response->json('run');
    expect($data['id'])->toBe($run->id);
    expect($data['context_data'])->toBe(['key' => 'value']);
    expect($data['steps'])->toHaveCount(1);
    expect($data['steps'][0]['node_id'])->toBe('trigger-1');
    expect($data['steps'][0]['input_data'])->toBe(['input' => 'test']);
    expect($data['steps'][0]['output_data'])->toBe(['output' => 'result']);
});

it('returns 404 for non-existent workflow when listing runs', function () {
    $response = $this->getJson('/workflow/api/workflows/nonexistent/runs');
    $response->assertNotFound();
});

it('returns 404 for non-existent run', function () {
    $response = $this->getJson('/workflow/api/workflow-runs/nonexistent');
    $response->assertNotFound();
});

it('returns empty runs array for workflow with no runs', function () {
    $workflow = Workflow::create([
        'name' => 'No Runs Test',
        'trigger_type' => TriggerType::Manual,
        'status' => 'draft',
    ]);

    $response = $this->getJson("/workflow/api/workflows/{$workflow->id}/runs");

    $response->assertOk();
    $response->assertJsonCount(0, 'runs');
});
