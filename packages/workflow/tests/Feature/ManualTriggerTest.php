<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Jobs\ExecuteWorkflowJob;
use Relaticle\Workflow\Models\Workflow;

it('triggers a manual workflow via API endpoint', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Manual Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'live',
    ]);
    $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);

    $response = $this->postJson("/workflow/api/workflows/{$workflow->id}/trigger");

    $response->assertOk();
    Queue::assertPushed(ExecuteWorkflowJob::class, function ($job) use ($workflow) {
        return $job->workflow->id === $workflow->id;
    });
});

it('passes record context when provided', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Manual Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'live',
    ]);
    $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);

    $response = $this->postJson("/workflow/api/workflows/{$workflow->id}/trigger", [
        'context' => ['record' => ['name' => 'Acme Corp']],
    ]);

    $response->assertOk();
    Queue::assertPushed(ExecuteWorkflowJob::class, function ($job) {
        return data_get($job->context, 'record.name') === 'Acme Corp';
    });
});

it('rejects trigger for inactive workflow', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Inactive Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'draft',
    ]);

    $response = $this->postJson("/workflow/api/workflows/{$workflow->id}/trigger");

    $response->assertStatus(422);
    Queue::assertNothingPushed();
});

it('rejects trigger for non-manual workflow', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Non-Manual Workflow',
        'trigger_type' => TriggerType::RecordEvent,
        'status' => 'live',
    ]);

    $response = $this->postJson("/workflow/api/workflows/{$workflow->id}/trigger");

    $response->assertStatus(422);
    Queue::assertNothingPushed();
});

it('returns 404 for non-existent workflow', function () {
    Queue::fake();

    $response = $this->postJson('/workflow/api/workflows/nonexistent-id/trigger');

    $response->assertNotFound();
    Queue::assertNothingPushed();
});
