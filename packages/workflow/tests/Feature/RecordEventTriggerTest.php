<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Facades\Workflow;
use Relaticle\Workflow\Jobs\ExecuteWorkflowJob;
use Relaticle\Workflow\Models\Workflow as WorkflowModel;
use Relaticle\Workflow\Tests\Fixtures\TestCompany;

beforeEach(function () {
    Queue::fake();

    // Reset the manager state between tests so observer attachments are clean
    app()->forgetInstance(\Relaticle\Workflow\WorkflowManager::class);
    app()->singleton(\Relaticle\Workflow\WorkflowManager::class);

    Workflow::registerTriggerableModel(TestCompany::class, [
        'label' => 'Company',
        'events' => ['created', 'updated', 'deleted'],
        'fields' => fn () => [
            'name' => ['type' => 'string', 'label' => 'Name'],
            'domain' => ['type' => 'string', 'label' => 'Domain'],
            'status' => ['type' => 'string', 'label' => 'Status'],
        ],
    ]);
});

it('dispatches job when a matching model is created', function () {
    WorkflowModel::create([
        'name' => 'On Company Created',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => [
            'model' => TestCompany::class,
            'event' => 'created',
        ],
        'is_active' => true,
    ]);

    TestCompany::create([
        'name' => 'Acme Corp',
        'domain' => 'acme.com',
    ]);

    Queue::assertPushed(ExecuteWorkflowJob::class, function (ExecuteWorkflowJob $job) {
        return $job->workflow->name === 'On Company Created';
    });
});

it('dispatches job when a specific field changes to a target value', function () {
    WorkflowModel::create([
        'name' => 'On Status Active',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => [
            'model' => TestCompany::class,
            'event' => 'updated',
            'field' => 'status',
            'value' => 'active',
        ],
        'is_active' => true,
    ]);

    $company = TestCompany::create([
        'name' => 'Acme Corp',
        'status' => 'draft',
    ]);

    // Reset the queue fake to ignore the create event
    Queue::fake();

    $company->update(['status' => 'active']);

    Queue::assertPushed(ExecuteWorkflowJob::class, function (ExecuteWorkflowJob $job) {
        return $job->workflow->name === 'On Status Active';
    });
});

it('does not trigger for unrelated field changes', function () {
    WorkflowModel::create([
        'name' => 'On Status Active',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => [
            'model' => TestCompany::class,
            'event' => 'updated',
            'field' => 'status',
            'value' => 'active',
        ],
        'is_active' => true,
    ]);

    $company = TestCompany::create([
        'name' => 'Acme Corp',
        'status' => 'draft',
    ]);

    // Reset the queue fake to ignore the create event
    Queue::fake();

    $company->update(['domain' => 'newdomain.com']);

    Queue::assertNotPushed(ExecuteWorkflowJob::class);
});

it('skips inactive workflows', function () {
    WorkflowModel::create([
        'name' => 'Inactive Workflow',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => [
            'model' => TestCompany::class,
            'event' => 'created',
        ],
        'is_active' => false,
    ]);

    TestCompany::create([
        'name' => 'Acme Corp',
    ]);

    Queue::assertNotPushed(ExecuteWorkflowJob::class);
});

it('passes record as context data in dispatched job', function () {
    WorkflowModel::create([
        'name' => 'With Context',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => [
            'model' => TestCompany::class,
            'event' => 'created',
        ],
        'is_active' => true,
    ]);

    TestCompany::create([
        'name' => 'Acme Corp',
        'domain' => 'acme.com',
        'status' => 'draft',
    ]);

    Queue::assertPushed(ExecuteWorkflowJob::class, function (ExecuteWorkflowJob $job) {
        return isset($job->context['record'])
            && $job->context['record']['name'] === 'Acme Corp'
            && $job->context['record']['domain'] === 'acme.com'
            && $job->context['event'] === 'created';
    });
});

it('dispatches job when a model is deleted', function () {
    WorkflowModel::create([
        'name' => 'On Company Deleted',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => [
            'model' => TestCompany::class,
            'event' => 'deleted',
        ],
        'is_active' => true,
    ]);

    $company = TestCompany::create([
        'name' => 'Acme Corp',
    ]);

    // Reset the queue fake to ignore the create event
    Queue::fake();

    $company->delete();

    Queue::assertPushed(ExecuteWorkflowJob::class, function (ExecuteWorkflowJob $job) {
        return $job->workflow->name === 'On Company Deleted'
            && $job->context['event'] === 'deleted';
    });
});

it('dispatches job for generic update without field filter', function () {
    WorkflowModel::create([
        'name' => 'On Any Update',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => [
            'model' => TestCompany::class,
            'event' => 'updated',
        ],
        'is_active' => true,
    ]);

    $company = TestCompany::create([
        'name' => 'Acme Corp',
    ]);

    // Reset the queue fake to ignore the create event
    Queue::fake();

    $company->update(['domain' => 'acme.com']);

    Queue::assertPushed(ExecuteWorkflowJob::class, function (ExecuteWorkflowJob $job) {
        return $job->workflow->name === 'On Any Update';
    });
});

it('does not dispatch when field changes but value does not match', function () {
    WorkflowModel::create([
        'name' => 'On Status Active',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => [
            'model' => TestCompany::class,
            'event' => 'updated',
            'field' => 'status',
            'value' => 'active',
        ],
        'is_active' => true,
    ]);

    $company = TestCompany::create([
        'name' => 'Acme Corp',
        'status' => 'draft',
    ]);

    // Reset the queue fake to ignore the create event
    Queue::fake();

    $company->update(['status' => 'archived']);

    Queue::assertNotPushed(ExecuteWorkflowJob::class);
});
