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
        'is_active' => true,
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

    expect($metrics['total_runs'])->toBe(2);
});

it('calculates success rate', function () {
    $workflow = Workflow::create([
        'name' => 'Test',
        'trigger_type' => TriggerType::Manual,
        'is_active' => true,
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

    expect($metrics['success_rate'])->toBe(75.0);
});

it('counts active workflows', function () {
    Workflow::create(['name' => 'Active', 'trigger_type' => TriggerType::Manual, 'is_active' => true]);
    Workflow::create(['name' => 'Inactive', 'trigger_type' => TriggerType::Manual, 'is_active' => false]);
    Workflow::create(['name' => 'Active2', 'trigger_type' => TriggerType::Manual, 'is_active' => true]);

    $widget = new WorkflowStatsWidget();
    $metrics = $widget->getMetrics();

    expect($metrics['active_workflows'])->toBe(2);
});

it('handles zero runs gracefully', function () {
    $widget = new WorkflowStatsWidget();
    $metrics = $widget->getMetrics();

    expect($metrics['total_runs'])->toBe(0);
    expect($metrics['success_rate'])->toBe(0.0);
    expect($metrics['active_workflows'])->toBe(0);
});

it('counts failed runs', function () {
    $workflow = Workflow::create([
        'name' => 'Test',
        'trigger_type' => TriggerType::Manual,
        'is_active' => true,
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

    expect($metrics['failed_runs'])->toBe(2);
});

it('excludes pending and running from success rate calculation', function () {
    $workflow = Workflow::create([
        'name' => 'Test',
        'trigger_type' => TriggerType::Manual,
        'is_active' => true,
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
    expect($metrics['total_runs'])->toBe(3);
    // Success rate = completed / total * 100
    expect($metrics['success_rate'])->toBeGreaterThan(0.0);
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
