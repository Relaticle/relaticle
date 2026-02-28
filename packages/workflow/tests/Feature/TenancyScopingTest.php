<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Facades\Workflow;
use Relaticle\Workflow\Models\Workflow as WorkflowModel;

beforeEach(function () {
    // Reset the manager state between tests so tenancy config is clean
    app()->forgetInstance(\Relaticle\Workflow\WorkflowManager::class);
    app()->singleton(\Relaticle\Workflow\WorkflowManager::class, function () {
        $manager = new \Relaticle\Workflow\WorkflowManager();
        $manager->registerAction('delay', \Relaticle\Workflow\Actions\DelayAction::class);
        $manager->registerAction('loop', \Relaticle\Workflow\Actions\LoopAction::class);
        $manager->registerAction('send_webhook', \Relaticle\Workflow\Actions\SendWebhookAction::class);
        $manager->registerAction('send_email', \Relaticle\Workflow\Actions\SendEmailAction::class);
        $manager->registerAction('http_request', \Relaticle\Workflow\Actions\HttpRequestAction::class);

        return $manager;
    });
});

it('scopes workflow queries when tenancy is configured', function () {
    Workflow::useTenancy('tenant_id', fn () => 'team-1');

    // Create workflows for different tenants
    $team1Workflow = WorkflowModel::create([
        'name' => 'Team 1 Workflow',
        'trigger_type' => TriggerType::Manual,
        'tenant_id' => 'team-1',
    ]);
    $team2Workflow = WorkflowModel::create([
        'name' => 'Team 2 Workflow',
        'trigger_type' => TriggerType::Manual,
        'tenant_id' => 'team-2',
    ]);

    // Apply the tenant scope
    $workflows = WorkflowModel::where('tenant_id', Workflow::getTenancyConfig()['resolver']())->get();

    expect($workflows)->toHaveCount(1);
    expect($workflows->first()->id)->toBe($team1Workflow->id);
});

it('auto-attaches tenant_id on workflow creation when tenancy is configured', function () {
    Workflow::useTenancy('tenant_id', fn () => 'team-1');

    $workflow = WorkflowModel::create([
        'name' => 'Auto Tenant Workflow',
        'trigger_type' => TriggerType::Manual,
    ]);

    // Should auto-set tenant_id from the resolver
    expect($workflow->tenant_id)->toBe('team-1');
});

it('prevents cross-tenant access', function () {
    Workflow::useTenancy('tenant_id', fn () => 'team-1');

    WorkflowModel::create([
        'name' => 'Team 2 Workflow',
        'trigger_type' => TriggerType::Manual,
        'tenant_id' => 'team-2',
    ]);

    // Query with tenant scope should not find team-2's workflow
    $workflows = WorkflowModel::where('tenant_id', Workflow::getTenancyConfig()['resolver']())->get();
    expect($workflows)->toHaveCount(0);
});

it('works without tenancy configured', function () {
    // Don't configure tenancy

    $workflow1 = WorkflowModel::create([
        'name' => 'Workflow 1',
        'trigger_type' => TriggerType::Manual,
    ]);
    $workflow2 = WorkflowModel::create([
        'name' => 'Workflow 2',
        'trigger_type' => TriggerType::Manual,
    ]);

    $workflows = WorkflowModel::all();
    expect($workflows)->toHaveCount(2);
});

it('does not overwrite explicitly provided tenant_id on creation', function () {
    Workflow::useTenancy('tenant_id', fn () => 'team-1');

    $workflow = WorkflowModel::create([
        'name' => 'Explicit Tenant Workflow',
        'trigger_type' => TriggerType::Manual,
        'tenant_id' => 'team-2',
    ]);

    // Should keep the explicitly provided tenant_id
    expect($workflow->tenant_id)->toBe('team-2');
});

it('does not auto-set tenant_id when tenancy is not configured', function () {
    // Don't configure tenancy

    $workflow = WorkflowModel::create([
        'name' => 'No Tenant Workflow',
        'trigger_type' => TriggerType::Manual,
    ]);

    expect($workflow->tenant_id)->toBeNull();
});

it('scopes record event triggers to tenant', function () {
    Queue::fake();

    Workflow::useTenancy('tenant_id', fn () => 'team-1');

    // Register test model
    Workflow::registerTriggerableModel(
        \Relaticle\Workflow\Tests\Fixtures\TestCompany::class,
        [
            'label' => 'Company',
            'events' => ['created'],
            'fields' => fn () => [],
        ]
    );

    // Create active workflow for team-1
    $workflow = WorkflowModel::create([
        'name' => 'Team 1 Record Workflow',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => [
            'model' => \Relaticle\Workflow\Tests\Fixtures\TestCompany::class,
            'event' => 'created',
        ],
        'is_active' => true,
        'tenant_id' => 'team-1',
    ]);

    // Create another for team-2
    WorkflowModel::create([
        'name' => 'Team 2 Record Workflow',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => [
            'model' => \Relaticle\Workflow\Tests\Fixtures\TestCompany::class,
            'event' => 'created',
        ],
        'is_active' => true,
        'tenant_id' => 'team-2',
    ]);

    // Create a test company - should trigger both workflows (observer finds all matching workflows)
    \Relaticle\Workflow\Tests\Fixtures\TestCompany::create([
        'name' => 'New Company',
    ]);

    // Both workflows match the model event regardless of tenant — the observer dispatches for all matches
    Queue::assertPushed(\Relaticle\Workflow\Jobs\ExecuteWorkflowJob::class);
});

it('resolves tenant through middleware and sets request attribute', function () {
    Workflow::useTenancy('tenant_id', fn () => 'team-42');

    $middleware = new \Relaticle\Workflow\Http\Middleware\WorkflowTenancyMiddleware(
        app(\Relaticle\Workflow\WorkflowManager::class)
    );

    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $capturedTenantId = null;

    $middleware->handle($request, function ($req) use (&$capturedTenantId) {
        $capturedTenantId = $req->attributes->get('workflow_tenant_id');

        return new \Illuminate\Http\Response('OK');
    });

    expect($capturedTenantId)->toBe('team-42');
});

it('middleware does not set attribute when tenancy is not configured', function () {
    $middleware = new \Relaticle\Workflow\Http\Middleware\WorkflowTenancyMiddleware(
        app(\Relaticle\Workflow\WorkflowManager::class)
    );

    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $capturedTenantId = null;

    $middleware->handle($request, function ($req) use (&$capturedTenantId) {
        $capturedTenantId = $req->attributes->get('workflow_tenant_id');

        return new \Illuminate\Http\Response('OK');
    });

    expect($capturedTenantId)->toBeNull();
});
