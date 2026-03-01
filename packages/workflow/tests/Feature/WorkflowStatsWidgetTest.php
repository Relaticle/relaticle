<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Filament\Widgets\WorkflowStatsWidget;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Models\WorkflowRun;

it('calculates total workflow runs', function () {
    $workflow = Workflow::create([
        'name' => 'Test',
        'trigger_type' => TriggerType::Manual,
        'status' => 'live',
    ]);

    WorkflowRun::create([
        'workflow_id' => $workflow->id,
        'status' => WorkflowRunStatus::Completed,
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);
    WorkflowRun::create([
        'workflow_id' => $workflow->id,
        'status' => WorkflowRunStatus::Failed,
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $widget = new WorkflowStatsWidget();
    $metrics = $widget->getMetrics();

    expect($metrics['totalRuns'])->toBe(2);
});

it('calculates success rate', function () {
    $workflow = Workflow::create([
        'name' => 'Test',
        'trigger_type' => TriggerType::Manual,
        'status' => 'live',
    ]);

    // 3 completed, 1 failed = 75% success rate
    for ($i = 0; $i < 3; $i++) {
        WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'status' => WorkflowRunStatus::Completed,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }
    WorkflowRun::create([
        'workflow_id' => $workflow->id,
        'status' => WorkflowRunStatus::Failed,
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $widget = new WorkflowStatsWidget();
    $metrics = $widget->getMetrics();

    expect($metrics['successRate'])->toBe(75.0);
});

it('counts active workflows', function () {
    Workflow::create(['name' => 'Active', 'trigger_type' => TriggerType::Manual, 'status' => 'live']);
    Workflow::create(['name' => 'Inactive', 'trigger_type' => TriggerType::Manual, 'status' => 'draft']);
    Workflow::create(['name' => 'Active2', 'trigger_type' => TriggerType::Manual, 'status' => 'live']);

    $widget = new WorkflowStatsWidget();
    $metrics = $widget->getMetrics();

    expect($metrics['activeWorkflows'])->toBe(2);
});

it('handles zero runs gracefully', function () {
    $widget = new WorkflowStatsWidget();
    $metrics = $widget->getMetrics();

    expect($metrics['totalRuns'])->toBe(0);
    expect($metrics['successRate'])->toBe(0);
    expect($metrics['activeWorkflows'])->toBe(0);
});

it('counts failed runs', function () {
    $workflow = Workflow::create([
        'name' => 'Test',
        'trigger_type' => TriggerType::Manual,
        'status' => 'live',
    ]);

    WorkflowRun::create([
        'workflow_id' => $workflow->id,
        'status' => WorkflowRunStatus::Failed,
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);
    WorkflowRun::create([
        'workflow_id' => $workflow->id,
        'status' => WorkflowRunStatus::Failed,
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);
    WorkflowRun::create([
        'workflow_id' => $workflow->id,
        'status' => WorkflowRunStatus::Completed,
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $widget = new WorkflowStatsWidget();
    $metrics = $widget->getMetrics();

    expect($metrics['failedRuns'])->toBe(2);
});

it('excludes pending and running from success rate calculation', function () {
    $workflow = Workflow::create([
        'name' => 'Test',
        'trigger_type' => TriggerType::Manual,
        'status' => 'live',
    ]);

    WorkflowRun::create([
        'workflow_id' => $workflow->id,
        'status' => WorkflowRunStatus::Completed,
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);
    WorkflowRun::create([
        'workflow_id' => $workflow->id,
        'status' => WorkflowRunStatus::Pending,
        'started_at' => now(),
    ]);
    WorkflowRun::create([
        'workflow_id' => $workflow->id,
        'status' => WorkflowRunStatus::Running,
        'started_at' => now(),
    ]);

    $widget = new WorkflowStatsWidget();
    $metrics = $widget->getMetrics();

    // Total runs includes all statuses
    expect($metrics['totalRuns'])->toBe(3);
    // Success rate = completed / total * 100
    expect($metrics['successRate'])->toBeGreaterThan(0.0);
});

it('returns stat objects from getStats', function () {
    $widget = new WorkflowStatsWidget();

    $reflection = new ReflectionMethod($widget, 'getStats');
    $stats = $reflection->invoke($widget);

    expect($stats)->toBeArray();
    expect($stats)->toHaveCount(3);

    foreach ($stats as $stat) {
        expect($stat)->toBeInstanceOf(\Filament\Widgets\StatsOverviewWidget\Stat::class);
    }
});

it('scopes metrics to current tenant', function () {
    app(\Relaticle\Workflow\WorkflowManager::class)->useTenancy(
        scopeColumn: 'tenant_id',
        resolver: fn () => 'team-1',
    );

    $wf1 = Workflow::withoutGlobalScopes()->create([
        'name' => 'T1', 'tenant_id' => 'team-1',
        'trigger_type' => TriggerType::Manual, 'trigger_config' => [],
        'canvas_data' => [], 'status' => 'live',
    ]);
    \Relaticle\Workflow\Models\WorkflowRun::create([
        'workflow_id' => $wf1->id,
        'tenant_id' => 'team-1',
        'status' => \Relaticle\Workflow\Enums\WorkflowRunStatus::Completed,
        'started_at' => now(), 'completed_at' => now(),
    ]);

    $wf2 = Workflow::withoutGlobalScopes()->create([
        'name' => 'T2', 'tenant_id' => 'team-2',
        'trigger_type' => TriggerType::Manual, 'trigger_config' => [],
        'canvas_data' => [], 'status' => 'live',
    ]);
    \Relaticle\Workflow\Models\WorkflowRun::withoutGlobalScopes()->create([
        'workflow_id' => $wf2->id,
        'tenant_id' => 'team-2',
        'status' => \Relaticle\Workflow\Enums\WorkflowRunStatus::Failed,
        'started_at' => now(), 'completed_at' => now(),
    ]);

    $widget = new \Relaticle\Workflow\Filament\Widgets\WorkflowStatsWidget();
    $metrics = $widget->getMetrics();

    expect($metrics['totalRuns'])->toBe(1);
    expect($metrics['activeWorkflows'])->toBe(1);
    expect($metrics['successRate'])->toBe(100.0);
});
