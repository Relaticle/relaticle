<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowStatus;
use Relaticle\Workflow\Models\Workflow;

it('publishes a draft workflow with valid nodes', function () {
    $workflow = Workflow::create([
        'name' => 'Publishable Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'draft',
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger-1',
        'type' => NodeType::Trigger,
        'position_x' => 0,
        'position_y' => 0,
    ]);
    $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
        'position_x' => 0,
        'position_y' => 100,
    ]);

    $response = $this->postJson("/workflow/api/workflows/{$workflow->id}/publish");

    $response->assertOk();
    $response->assertJson(['status' => 'live']);

    $workflow->refresh();
    expect($workflow->status)->toBe(WorkflowStatus::Live)
        ->and($workflow->published_at)->not->toBeNull();
});

it('rejects publishing workflow without required nodes', function () {
    $workflow = Workflow::create([
        'name' => 'Empty Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'draft',
    ]);

    $response = $this->postJson("/workflow/api/workflows/{$workflow->id}/publish");

    $response->assertUnprocessable();
    $response->assertJsonStructure(['errors']);
});

it('pauses a live workflow', function () {
    $workflow = Workflow::create([
        'name' => 'Live Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'live',
    ]);

    $response = $this->postJson("/workflow/api/workflows/{$workflow->id}/pause");

    $response->assertOk();
    $response->assertJson(['status' => 'paused']);

    $workflow->refresh();
    expect($workflow->status)->toBe(WorkflowStatus::Paused);
});

it('rejects pausing a non-live workflow', function () {
    $workflow = Workflow::create([
        'name' => 'Draft Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'draft',
    ]);

    $response = $this->postJson("/workflow/api/workflows/{$workflow->id}/pause");

    $response->assertUnprocessable();
});

it('archives a workflow', function () {
    $workflow = Workflow::create([
        'name' => 'Archivable Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'live',
    ]);

    $response = $this->postJson("/workflow/api/workflows/{$workflow->id}/archive");

    $response->assertOk();
    $response->assertJson(['status' => 'archived']);

    $workflow->refresh();
    expect($workflow->status)->toBe(WorkflowStatus::Archived);
});

it('restores an archived workflow to paused', function () {
    $workflow = Workflow::create([
        'name' => 'Archived Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'archived',
    ]);

    $response = $this->postJson("/workflow/api/workflows/{$workflow->id}/restore");

    $response->assertOk();
    $response->assertJson(['status' => 'paused']);

    $workflow->refresh();
    expect($workflow->status)->toBe(WorkflowStatus::Paused);
});

it('rejects restoring a non-archived workflow', function () {
    $workflow = Workflow::create([
        'name' => 'Draft Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'draft',
    ]);

    $response = $this->postJson("/workflow/api/workflows/{$workflow->id}/restore");

    $response->assertUnprocessable();
});
