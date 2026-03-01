<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\WorkflowStatus;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Triggers\ManualTrigger;
use Relaticle\Workflow\Triggers\RecordEventTrigger;
use Relaticle\Workflow\Enums\TriggerType;

it('creates workflows in draft status by default', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::Manual,
    ]);
    $workflow->refresh();

    expect($workflow->status)->toBe(WorkflowStatus::Draft);
});

it('only triggers workflows in live status', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'draft',
    ]);

    expect($workflow->status->canTrigger())->toBeFalse();

    $workflow->update(['status' => 'live']);
    $workflow->refresh();

    expect($workflow->status->canTrigger())->toBeTrue();
});

it('publishes a draft workflow to live', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'draft',
    ]);

    $workflow->update(['status' => WorkflowStatus::Live, 'published_at' => now()]);
    $workflow->refresh();

    expect($workflow->status)->toBe(WorkflowStatus::Live)
        ->and($workflow->published_at)->not->toBeNull();
});

it('pauses a live workflow', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'live',
    ]);

    $workflow->update(['status' => WorkflowStatus::Paused]);
    $workflow->refresh();

    expect($workflow->status)->toBe(WorkflowStatus::Paused)
        ->and($workflow->status->canTrigger())->toBeFalse();
});

it('archives a workflow', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'live',
    ]);

    $workflow->update(['status' => WorkflowStatus::Archived]);
    $workflow->refresh();

    expect($workflow->status)->toBe(WorkflowStatus::Archived)
        ->and($workflow->status->canTrigger())->toBeFalse();
});

it('restores archived workflow to paused', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'archived',
    ]);

    $workflow->update(['status' => WorkflowStatus::Paused]);
    $workflow->refresh();

    expect($workflow->status)->toBe(WorkflowStatus::Paused);
});

it('preserves backward compat via is_active accessor', function () {
    $liveWorkflow = Workflow::create([
        'name' => 'Live Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'live',
    ]);

    $draftWorkflow = Workflow::create([
        'name' => 'Draft Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'draft',
    ]);

    expect($liveWorkflow->is_active)->toBeTrue()
        ->and($draftWorkflow->is_active)->toBeFalse();
});
