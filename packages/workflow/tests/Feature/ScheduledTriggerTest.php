<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Jobs\EvaluateScheduledWorkflowsJob;
use Relaticle\Workflow\Jobs\ExecuteWorkflowJob;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Tests\Fixtures\TestCompany;

beforeEach(function () {
    Queue::fake();
});

it('dispatches job when cron expression matches', function () {
    // "* * * * *" matches every minute, so it should always be due
    Workflow::create([
        'name' => 'Every Minute Workflow',
        'trigger_type' => TriggerType::TimeBased,
        'trigger_config' => [
            'schedule_type' => 'cron',
            'cron' => '* * * * *',
        ],
        'status' => 'live',
    ]);

    (new EvaluateScheduledWorkflowsJob())->handle(app(\Relaticle\Workflow\Triggers\ScheduledTrigger::class));

    Queue::assertPushed(ExecuteWorkflowJob::class, function (ExecuteWorkflowJob $job) {
        return $job->workflow->name === 'Every Minute Workflow';
    });
});

it('does not dispatch when cron expression does not match', function () {
    // Set current time to a Wednesday at 10:00, then use a cron that only runs on Sunday at 03:00
    Carbon::setTestNow(Carbon::create(2026, 3, 4, 10, 0, 0)); // Wednesday

    Workflow::create([
        'name' => 'Sunday Only Workflow',
        'trigger_type' => TriggerType::TimeBased,
        'trigger_config' => [
            'schedule_type' => 'cron',
            'cron' => '0 3 * * 0', // Sunday at 03:00
        ],
        'status' => 'live',
    ]);

    (new EvaluateScheduledWorkflowsJob())->handle(app(\Relaticle\Workflow\Triggers\ScheduledTrigger::class));

    Queue::assertNotPushed(ExecuteWorkflowJob::class);

    Carbon::setTestNow(); // Reset
});

it('skips inactive workflows', function () {
    Workflow::create([
        'name' => 'Inactive Cron Workflow',
        'trigger_type' => TriggerType::TimeBased,
        'trigger_config' => [
            'schedule_type' => 'cron',
            'cron' => '* * * * *',
        ],
        'status' => 'draft',
    ]);

    (new EvaluateScheduledWorkflowsJob())->handle(app(\Relaticle\Workflow\Triggers\ScheduledTrigger::class));

    Queue::assertNotPushed(ExecuteWorkflowJob::class);
});

it('updates last_triggered_at after dispatch', function () {
    Carbon::setTestNow(Carbon::create(2026, 3, 1, 12, 0, 0));

    $workflow = Workflow::create([
        'name' => 'Track Last Triggered',
        'trigger_type' => TriggerType::TimeBased,
        'trigger_config' => [
            'schedule_type' => 'cron',
            'cron' => '* * * * *',
        ],
        'status' => 'live',
    ]);

    expect($workflow->last_triggered_at)->toBeNull();

    (new EvaluateScheduledWorkflowsJob())->handle(app(\Relaticle\Workflow\Triggers\ScheduledTrigger::class));

    $workflow->refresh();

    expect($workflow->last_triggered_at)->not->toBeNull();
    expect($workflow->last_triggered_at->toDateTimeString())->toBe('2026-03-01 12:00:00');

    Carbon::setTestNow();
});

it('dispatches for stale records with inactivity trigger', function () {
    Workflow::create([
        'name' => 'Inactivity Alert',
        'trigger_type' => TriggerType::TimeBased,
        'trigger_config' => [
            'schedule_type' => 'inactivity',
            'model' => TestCompany::class,
            'inactive_days' => 30,
        ],
        'status' => 'live',
    ]);

    // Create a company that was last updated 31 days ago
    $staleCompany = TestCompany::create([
        'name' => 'Stale Corp',
        'status' => 'active',
    ]);
    $staleCompany->updated_at = now()->subDays(31);
    $staleCompany->saveQuietly();

    (new EvaluateScheduledWorkflowsJob())->handle(app(\Relaticle\Workflow\Triggers\ScheduledTrigger::class));

    Queue::assertPushed(ExecuteWorkflowJob::class, function (ExecuteWorkflowJob $job) {
        return $job->workflow->name === 'Inactivity Alert';
    });
});

it('does not dispatch for recently updated records with inactivity trigger', function () {
    Workflow::create([
        'name' => 'Inactivity Alert',
        'trigger_type' => TriggerType::TimeBased,
        'trigger_config' => [
            'schedule_type' => 'inactivity',
            'model' => TestCompany::class,
            'inactive_days' => 30,
        ],
        'status' => 'live',
    ]);

    // Create a company that was last updated 5 days ago — should NOT trigger
    $recentCompany = TestCompany::create([
        'name' => 'Active Corp',
        'status' => 'active',
    ]);
    $recentCompany->updated_at = now()->subDays(5);
    $recentCompany->saveQuietly();

    (new EvaluateScheduledWorkflowsJob())->handle(app(\Relaticle\Workflow\Triggers\ScheduledTrigger::class));

    Queue::assertNotPushed(ExecuteWorkflowJob::class);
});
