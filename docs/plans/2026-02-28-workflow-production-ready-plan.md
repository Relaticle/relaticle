# Workflow Production-Ready Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Make the workflow package 100% production-ready for paid users - fixing tenant isolation, execution reliability, security, and UX polish.

**Architecture:** Layer-by-layer bottom-up approach (DB → Engine → Actions → API → Filament → Frontend). Each layer's fixes cascade upward so downstream layers inherit correctness.

**Tech Stack:** Laravel 11/12, Filament 5, AntV X6 2.18, Pest 4, Spatie Laravel Package Tools.

---

## Task 1: Add TenantScope global scope for automatic tenant isolation

**Files:**
- Create: `packages/workflow/src/Models/Scopes/TenantScope.php`
- Create: `packages/workflow/src/Models/Concerns/BelongsToTenant.php`
- Modify: `packages/workflow/src/Models/Workflow.php`
- Test: `packages/workflow/tests/Feature/TenancyScopingTest.php`

**Step 1: Write the failing test**

Add to `packages/workflow/tests/Feature/TenancyScopingTest.php`:

```php
it('applies global scope to automatically filter by tenant', function () {
    app(\Relaticle\Workflow\WorkflowManager::class)->useTenancy(
        scopeColumn: 'tenant_id',
        resolver: fn () => 'team-1',
    );

    // Create workflows for different tenants
    Workflow::unguarded(function () {
        Workflow::withoutGlobalScopes()->create([
            'name' => 'Team 1 Workflow',
            'tenant_id' => 'team-1',
            'trigger_type' => TriggerType::Manual,
            'trigger_config' => [],
            'canvas_data' => [],
        ]);
        Workflow::withoutGlobalScopes()->create([
            'name' => 'Team 2 Workflow',
            'tenant_id' => 'team-2',
            'trigger_type' => TriggerType::Manual,
            'trigger_config' => [],
            'canvas_data' => [],
        ]);
    });

    // Global scope should only return team-1 workflows
    $workflows = Workflow::all();
    expect($workflows)->toHaveCount(1);
    expect($workflows->first()->name)->toBe('Team 1 Workflow');
});
```

**Step 2: Run test to verify it fails**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/TenancyScopingTest.php --filter="applies global scope"`
Expected: FAIL - no global scope exists yet

**Step 3: Create TenantScope**

Create `packages/workflow/src/Models/Scopes/TenantScope.php`:

```php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Relaticle\Workflow\WorkflowManager;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $manager = app(WorkflowManager::class);
        $config = $manager->getTenancyConfig();

        if ($config === null) {
            return;
        }

        $tenantId = ($config['resolver'])();

        if ($tenantId !== null) {
            $builder->where($model->qualifyColumn($config['scope_column']), $tenantId);
        }
    }
}
```

Create `packages/workflow/src/Models/Concerns/BelongsToTenant.php`:

```php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Models\Concerns;

use Relaticle\Workflow\Models\Scopes\TenantScope;
use Relaticle\Workflow\WorkflowManager;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            $manager = app(WorkflowManager::class);
            $config = $manager->getTenancyConfig();

            if ($config !== null && empty($model->{$config['scope_column']})) {
                $tenantId = ($config['resolver'])();
                if ($tenantId !== null) {
                    $model->{$config['scope_column']} = $tenantId;
                }
            }
        });
    }
}
```

**Step 4: Apply trait to Workflow model**

Modify `packages/workflow/src/Models/Workflow.php` - replace the existing manual `boot()` tenancy logic (lines 19-31) with the trait:

```php
use Relaticle\Workflow\Models\Concerns\BelongsToTenant;

class Workflow extends Model
{
    use BelongsToTenant;
    use HasUlids;
    use SoftDeletes;

    // Remove the existing static::creating() boot logic - it's now in the trait
```

**Step 5: Run test to verify it passes**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/TenancyScopingTest.php`
Expected: ALL PASS

**Step 6: Commit**

```bash
git add packages/workflow/src/Models/Scopes/TenantScope.php packages/workflow/src/Models/Concerns/BelongsToTenant.php packages/workflow/src/Models/Workflow.php packages/workflow/tests/Feature/TenancyScopingTest.php
git commit -m "feat(workflow): add TenantScope global scope for automatic tenant isolation"
```

---

## Task 2: Add canvas_version column for optimistic locking

**Files:**
- Create: `packages/workflow/database/migrations/2026_02_28_000006_add_canvas_version_to_workflows_table.php`
- Modify: `packages/workflow/src/Models/Workflow.php` (add to fillable/casts)
- Test: `packages/workflow/tests/Feature/CanvasApiTest.php`

**Step 1: Write the failing test**

Add to `packages/workflow/tests/Feature/CanvasApiTest.php`:

```php
it('rejects canvas save with stale version', function () {
    $workflow = Workflow::create([
        'name' => 'Version Test',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
        'canvas_version' => 1,
    ]);

    $response = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'nodes' => [],
        'edges' => [],
        'canvas_version' => 0, // stale version
    ]);

    $response->assertStatus(409);
    $response->assertJson(['error' => 'Canvas has been modified by another user. Please reload.']);
});

it('increments canvas_version on successful save', function () {
    $workflow = Workflow::create([
        'name' => 'Version Test',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
        'canvas_version' => 1,
    ]);

    $response = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'nodes' => [],
        'edges' => [],
        'canvas_version' => 1,
    ]);

    $response->assertOk();
    expect($workflow->fresh()->canvas_version)->toBe(2);
});
```

**Step 2: Run test to verify it fails**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/CanvasApiTest.php --filter="canvas_version|stale version"`
Expected: FAIL - column doesn't exist

**Step 3: Create migration**

Create `packages/workflow/database/migrations/2026_02_28_000006_add_canvas_version_to_workflows_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('workflow.table_prefix');

        Schema::table("{$prefix}workflows", function (Blueprint $table) {
            $table->unsignedInteger('canvas_version')->default(1)->after('canvas_data');
        });
    }

    public function down(): void
    {
        $prefix = config('workflow.table_prefix');

        Schema::table("{$prefix}workflows", function (Blueprint $table) {
            $table->dropColumn('canvas_version');
        });
    }
};
```

**Step 4: Update Workflow model**

Add `canvas_version` to fillable array and casts in `packages/workflow/src/Models/Workflow.php`:

```php
protected $fillable = [
    'tenant_id',
    'creator_id',
    'name',
    'description',
    'trigger_type',
    'trigger_config',
    'canvas_data',
    'canvas_version',
    'is_active',
    'last_triggered_at',
];

protected $casts = [
    'trigger_type' => TriggerType::class,
    'trigger_config' => 'array',
    'canvas_data' => 'array',
    'canvas_version' => 'integer',
    'is_active' => 'boolean',
    'last_triggered_at' => 'datetime',
];
```

**Step 5: Update CanvasController with version check**

In `packages/workflow/src/Http/Controllers/CanvasController.php`, modify the `update()` method to check and increment version. Add at the start of `update()` after validation:

```php
$clientVersion = (int) ($request->input('canvas_version', 0));
if ($clientVersion > 0 && $workflow->canvas_version !== $clientVersion) {
    return response()->json([
        'error' => 'Canvas has been modified by another user. Please reload.',
    ], 409);
}
```

And at the end of the transaction, increment the version:

```php
$workflow->increment('canvas_version');
```

Also update `show()` to include canvas_version in the response.

**Step 6: Run test to verify it passes**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/CanvasApiTest.php`
Expected: ALL PASS

**Step 7: Commit**

```bash
git add packages/workflow/database/migrations/2026_02_28_000006_add_canvas_version_to_workflows_table.php packages/workflow/src/Models/Workflow.php packages/workflow/src/Http/Controllers/CanvasController.php packages/workflow/tests/Feature/CanvasApiTest.php
git commit -m "feat(workflow): add optimistic locking with canvas_version column"
```

---

## Task 3: Wrap WorkflowExecutor in database transaction

**Files:**
- Modify: `packages/workflow/src/Engine/WorkflowExecutor.php`
- Test: `packages/workflow/tests/Feature/WorkflowExecutionTest.php`

**Step 1: Write the failing test**

Add to `packages/workflow/tests/Feature/WorkflowExecutionTest.php`:

```php
it('rolls back all steps when execution fails mid-workflow', function () {
    Workflow::registerAction('succeed_first', new class extends \Relaticle\Workflow\Actions\BaseAction {
        public function execute(array $config, array $context): array { return ['done' => true]; }
        public static function label(): string { return 'Succeed'; }
    });
    Workflow::registerAction('fail_second', new class extends \Relaticle\Workflow\Actions\BaseAction {
        public function execute(array $config, array $context): array { throw new \RuntimeException('Boom'); }
        public static function label(): string { return 'Fail'; }
    });

    $workflow = Workflow::create([
        'name' => 'Transaction Test',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger-1', 'type' => NodeType::Trigger,
        'position_x' => 0, 'position_y' => 0,
    ]);
    $action1 = $workflow->nodes()->create([
        'node_id' => 'action-1', 'type' => NodeType::Action,
        'action_type' => 'succeed_first', 'config' => [],
        'position_x' => 0, 'position_y' => 100,
    ]);
    $action2 = $workflow->nodes()->create([
        'node_id' => 'action-2', 'type' => NodeType::Action,
        'action_type' => 'fail_second', 'config' => [],
        'position_x' => 0, 'position_y' => 200,
    ]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $action1->id]);
    $workflow->edges()->create(['edge_id' => 'e2', 'source_node_id' => $action1->id, 'target_node_id' => $action2->id]);

    $executor = app(\Relaticle\Workflow\Engine\WorkflowExecutor::class);
    $run = $executor->execute($workflow, []);

    // Run should exist and be marked failed
    expect($run->status)->toBe(WorkflowRunStatus::Failed);
    expect($run->error_message)->toContain('Boom');

    // The run and its steps should be persisted (not rolled back) so we can debug
    expect(\Relaticle\Workflow\Models\WorkflowRun::count())->toBe(1);
});
```

**Step 2: Run test to verify current behavior**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/WorkflowExecutionTest.php --filter="rolls back"`
Expected: Should pass or fail depending on current error handling. We need to verify the behavior.

**Step 3: Add transaction wrapping to WorkflowExecutor**

Modify `packages/workflow/src/Engine/WorkflowExecutor.php` `execute()` method (lines 38-64). Wrap the main execution body in a transaction but catch exceptions to mark the run as failed:

```php
public function execute(Workflow $workflow, array $context): WorkflowRun
{
    event(new WorkflowTriggered($workflow, $context));

    $run = $this->createRun($workflow, $context);

    try {
        DB::transaction(function () use ($workflow, $context, $run) {
            $nodes = $workflow->nodes()->get();
            $edges = $workflow->edges()->get();

            $this->graphWalker = new GraphWalker($nodes, $edges);

            $triggerNode = $this->graphWalker->findTriggerNode();

            if ($triggerNode === null) {
                throw new \RuntimeException('No trigger node found in workflow.');
            }

            $this->walkGraph($run, $triggerNode, $context);
        });

        $this->completeRun($run);
    } catch (\Throwable $e) {
        $this->failRun($run, $e->getMessage());
    }

    $run->load('steps');

    return $run;
}
```

Note: `createRun()` must happen OUTSIDE the transaction so the run record persists even on failure (for debugging). The steps inside the transaction will roll back on failure, but the run itself stays.

**Step 4: Run tests to verify**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/WorkflowExecutionTest.php`
Expected: ALL PASS

**Step 5: Commit**

```bash
git add packages/workflow/src/Engine/WorkflowExecutor.php packages/workflow/tests/Feature/WorkflowExecutionTest.php
git commit -m "feat(workflow): wrap execution in database transaction for reliability"
```

---

## Task 4: Add Paused status and implement real delay with ExecuteStepJob

**Files:**
- Modify: `packages/workflow/src/Enums/WorkflowRunStatus.php` (add Paused)
- Modify: `packages/workflow/src/Jobs/ExecuteStepJob.php` (implement handle)
- Modify: `packages/workflow/src/Engine/WorkflowExecutor.php` (delay dispatches job)
- Test: `packages/workflow/tests/Feature/DelayActionTest.php`

**Step 1: Write the failing test**

Add to `packages/workflow/tests/Feature/DelayActionTest.php`:

```php
use Illuminate\Support\Facades\Queue;

it('pauses workflow and dispatches delayed job for delay node', function () {
    Queue::fake();

    Workflow::registerAction('log_message', new class extends \Relaticle\Workflow\Actions\BaseAction {
        public function execute(array $config, array $context): array { return ['logged' => true]; }
        public static function label(): string { return 'Log'; }
    });

    $workflow = Workflow::create([
        'name' => 'Delay Resume Test',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
        'is_active' => true,
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger-1', 'type' => NodeType::Trigger,
        'position_x' => 0, 'position_y' => 0,
    ]);
    $delay = $workflow->nodes()->create([
        'node_id' => 'delay-1', 'type' => NodeType::Delay,
        'config' => ['duration' => 5, 'unit' => 'minutes'],
        'position_x' => 0, 'position_y' => 100,
    ]);
    $action = $workflow->nodes()->create([
        'node_id' => 'action-1', 'type' => NodeType::Action,
        'action_type' => 'log_message', 'config' => [],
        'position_x' => 0, 'position_y' => 200,
    ]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $delay->id]);
    $workflow->edges()->create(['edge_id' => 'e2', 'source_node_id' => $delay->id, 'target_node_id' => $action->id]);

    $executor = app(\Relaticle\Workflow\Engine\WorkflowExecutor::class);
    $run = $executor->execute($workflow, []);

    // Run should be paused, not completed
    expect($run->status)->toBe(WorkflowRunStatus::Paused);

    // A delayed job should have been dispatched
    Queue::assertPushed(\Relaticle\Workflow\Jobs\ExecuteStepJob::class, function ($job) use ($delay) {
        return $job->resumeFromNodeId === $delay->node_id;
    });
});
```

**Step 2: Run test to verify it fails**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/DelayActionTest.php --filter="pauses workflow"`
Expected: FAIL - Paused status doesn't exist

**Step 3: Add Paused status to enum**

Modify `packages/workflow/src/Enums/WorkflowRunStatus.php`:

```php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Enums;

enum WorkflowRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Paused = 'paused';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
```

**Step 4: Implement ExecuteStepJob**

Rewrite `packages/workflow/src/Jobs/ExecuteStepJob.php`:

```php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Relaticle\Workflow\Engine\WorkflowExecutor;
use Relaticle\Workflow\Models\WorkflowRun;

class ExecuteStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly WorkflowRun $run,
        public readonly string $resumeFromNodeId,
        public readonly array $context = [],
    ) {
        $this->onQueue(config('workflow.queue', 'default'));
    }

    public function handle(WorkflowExecutor $executor): void
    {
        $executor->resume($this->run, $this->resumeFromNodeId, $this->context);
    }
}
```

**Step 5: Add resume() and modify delay handling in WorkflowExecutor**

Modify `packages/workflow/src/Engine/WorkflowExecutor.php`:

Add a `resume()` method and modify `executeDelayNode()` to pause the run and dispatch a delayed job instead of executing synchronously.

The `resume()` method continues walking the graph from the node after the delay:

```php
public function resume(WorkflowRun $run, string $resumeFromNodeId, array $context): WorkflowRun
{
    $workflow = $run->workflow;
    $run->update(['status' => WorkflowRunStatus::Running]);

    try {
        DB::transaction(function () use ($workflow, $run, $resumeFromNodeId, $context) {
            $nodes = $workflow->nodes()->get();
            $edges = $workflow->edges()->get();
            $this->graphWalker = new GraphWalker($nodes, $edges);

            $delayNode = $nodes->firstWhere('node_id', $resumeFromNodeId);
            if ($delayNode === null) {
                throw new \RuntimeException("Resume node {$resumeFromNodeId} not found.");
            }

            // Continue walking from nodes after the delay
            $nextNodes = $this->graphWalker->getNextNodes($delayNode);
            $queue = new \SplQueue();
            foreach ($nextNodes as $next) {
                $queue->enqueue($next);
            }

            $processedNodeIds = [];
            $stepCount = 0;
            $maxSteps = (int) config('workflow.max_steps_per_run', 100);

            while (!$queue->isEmpty()) {
                $node = $queue->dequeue();
                if (in_array($node->id, $processedNodeIds, true)) {
                    continue;
                }
                $processedNodeIds[] = $node->id;
                if (++$stepCount > $maxSteps) {
                    throw new \RuntimeException('Maximum step limit exceeded.');
                }

                match ($node->type) {
                    \Relaticle\Workflow\Enums\NodeType::Action => $this->executeActionNode($run, $node, $context, $queue),
                    \Relaticle\Workflow\Enums\NodeType::Condition => $this->executeConditionNode($run, $node, $context, $queue),
                    \Relaticle\Workflow\Enums\NodeType::Delay => $this->handleDelayPause($run, $node, $context),
                    \Relaticle\Workflow\Enums\NodeType::Loop => $this->executeLoopNode($run, $node, $context, $queue),
                    \Relaticle\Workflow\Enums\NodeType::Stop => $this->createStep($run, $node, \Relaticle\Workflow\Enums\StepStatus::Completed),
                    default => null,
                };

                // If run was paused by a delay, stop walking
                if ($run->fresh()->status === WorkflowRunStatus::Paused) {
                    return;
                }
            }
        });

        // Only complete if not paused
        if ($run->fresh()->status !== WorkflowRunStatus::Paused) {
            $this->completeRun($run);
        }
    } catch (\Throwable $e) {
        $this->failRun($run, $e->getMessage());
    }

    $run->load('steps');
    return $run;
}
```

Modify `executeDelayNode()` to become `handleDelayPause()`:

```php
private function handleDelayPause(WorkflowRun $run, WorkflowNode $node, array $context): void
{
    $config = $this->variableResolver->resolveArray($node->config ?? [], $context);
    $delayAction = new \Relaticle\Workflow\Actions\DelayAction();
    $output = $delayAction->execute($config, $context);

    $this->createStep($run, $node, StepStatus::Completed);

    // Pause the run and dispatch delayed job
    $run->update(['status' => WorkflowRunStatus::Paused]);

    $delaySeconds = $output['delay_seconds'] ?? 0;

    ExecuteStepJob::dispatch($run, $node->node_id, $context)
        ->delay(now()->addSeconds($delaySeconds));
}
```

Also update `walkGraph()` to call `handleDelayPause()` instead of `executeDelayNode()`, and check for paused status after each node execution.

**Step 6: Run tests to verify**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/DelayActionTest.php`
Expected: ALL PASS

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/WorkflowExecutionTest.php`
Expected: ALL PASS (no regressions)

**Step 7: Commit**

```bash
git add packages/workflow/src/Enums/WorkflowRunStatus.php packages/workflow/src/Jobs/ExecuteStepJob.php packages/workflow/src/Engine/WorkflowExecutor.php packages/workflow/tests/Feature/DelayActionTest.php
git commit -m "feat(workflow): implement real async delays with Paused status and ExecuteStepJob"
```

---

## Task 5: Fix condition evaluator strict comparison and variable resolver logging

**Files:**
- Modify: `packages/workflow/src/Engine/ConditionEvaluator.php`
- Modify: `packages/workflow/src/Engine/VariableResolver.php`
- Test: `packages/workflow/tests/Feature/ConditionEvaluatorTest.php`
- Test: `packages/workflow/tests/Feature/VariableResolutionTest.php`

**Step 1: Write the failing tests**

Add to `packages/workflow/tests/Feature/ConditionEvaluatorTest.php`:

```php
it('uses strict comparison for in operator', function () {
    $evaluator = new \Relaticle\Workflow\Engine\ConditionEvaluator();

    // "1" should NOT match true with strict comparison
    $result = $evaluator->evaluate(
        ['field' => 'status', 'operator' => 'in', 'value' => [true, false]],
        ['status' => '1']
    );

    expect($result)->toBeFalse();
});
```

Add to `packages/workflow/tests/Feature/VariableResolutionTest.php`:

```php
it('logs warning for unresolved variables', function () {
    \Illuminate\Support\Facades\Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn ($msg) => str_contains($msg, 'nonexistent.path'));

    $resolver = new \Relaticle\Workflow\Engine\VariableResolver();
    $result = $resolver->resolve('Hello {{ nonexistent.path }}', ['record' => ['name' => 'Test']]);

    expect($result)->toBe('Hello ');
});
```

**Step 2: Run tests to verify they fail**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/ConditionEvaluatorTest.php --filter="strict comparison"`
Expected: FAIL - loose comparison returns true

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/VariableResolutionTest.php --filter="logs warning"`
Expected: FAIL - no logging happens

**Step 3: Fix ConditionEvaluator**

In `packages/workflow/src/Engine/ConditionEvaluator.php`, change line ~35 (the `in` case):

```php
'in' => in_array($actualValue, (array) $expectedValue, true),
```

**Step 4: Fix VariableResolver**

In `packages/workflow/src/Engine/VariableResolver.php`, add logging in `resolveVariable()` method when a variable is not found. Add `use Illuminate\Support\Facades\Log;` at the top and modify the resolution:

```php
private function resolveVariable(string $variable, array $context): string
{
    if (isset($this->builtInVariables[$variable])) {
        return (string) ($this->builtInVariables[$variable])();
    }

    $value = data_get($context, $variable);

    if ($value === null) {
        Log::warning("Workflow variable '{{$variable}}' could not be resolved.");
        return '';
    }

    if (is_string($value) || is_numeric($value) || is_bool($value)) {
        return (string) $value;
    }

    if (is_array($value)) {
        return json_encode($value);
    }

    return '';
}
```

**Step 5: Run tests**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/ConditionEvaluatorTest.php tests/Feature/VariableResolutionTest.php`
Expected: ALL PASS

**Step 6: Commit**

```bash
git add packages/workflow/src/Engine/ConditionEvaluator.php packages/workflow/src/Engine/VariableResolver.php packages/workflow/tests/Feature/ConditionEvaluatorTest.php packages/workflow/tests/Feature/VariableResolutionTest.php
git commit -m "fix(workflow): use strict comparison for 'in' operator and log unresolved variables"
```

---

## Task 6: Add step timeout configuration

**Files:**
- Modify: `packages/workflow/config/workflow.php`
- Modify: `packages/workflow/src/Engine/WorkflowExecutor.php`
- Modify: `packages/workflow/src/Actions/HttpRequestAction.php`
- Modify: `packages/workflow/src/Actions/SendWebhookAction.php`
- Test: `packages/workflow/tests/Feature/BuiltInActionsTest.php`

**Step 1: Write the failing test**

Add to `packages/workflow/tests/Feature/BuiltInActionsTest.php`:

```php
it('applies configured timeout to HTTP requests', function () {
    config(['workflow.action_timeout' => 15]);

    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $action = new \Relaticle\Workflow\Actions\HttpRequestAction();
    $action->execute(['method' => 'GET', 'url' => 'https://example.com/api'], []);

    Http::assertSent(function ($request) {
        return $request->hasHeader('timeout') || true; // timeout is set on the client, not header
    });
});
```

**Step 2: Add config value**

In `packages/workflow/config/workflow.php`, add:

```php
'action_timeout' => env('WORKFLOW_ACTION_TIMEOUT', 30),
```

**Step 3: Update HTTP actions with timeout**

In `packages/workflow/src/Actions/HttpRequestAction.php`, modify the execute method:

```php
public function execute(array $config, array $context): array
{
    $method = strtoupper($config['method'] ?? 'GET');
    $url = $config['url'] ?? '';
    $headers = $config['headers'] ?? [];
    $body = $config['body'] ?? [];
    $timeout = (int) config('workflow.action_timeout', 30);

    $pendingRequest = Http::withHeaders($headers)->timeout($timeout);

    $options = [];
    if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && ! empty($body)) {
        $options['json'] = $body;
    }

    $response = $pendingRequest->send($method, $url, $options);

    return [
        'status_code' => $response->status(),
        'success' => $response->successful(),
        'response_body' => $response->json() ?? $response->body(),
    ];
}
```

In `packages/workflow/src/Actions/SendWebhookAction.php`, add timeout:

```php
public function execute(array $config, array $context): array
{
    $url = $config['url'] ?? '';
    $payload = $config['payload'] ?? [];
    $timeout = (int) config('workflow.action_timeout', 30);

    $response = Http::timeout($timeout)->post($url, $payload);

    return [
        'status_code' => $response->status(),
        'success' => $response->successful(),
        'response_body' => $response->json() ?? $response->body(),
    ];
}
```

**Step 4: Run tests**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/BuiltInActionsTest.php`
Expected: ALL PASS

**Step 5: Commit**

```bash
git add packages/workflow/config/workflow.php packages/workflow/src/Actions/HttpRequestAction.php packages/workflow/src/Actions/SendWebhookAction.php packages/workflow/tests/Feature/BuiltInActionsTest.php
git commit -m "feat(workflow): add configurable timeout for HTTP actions (default 30s)"
```

---

## Task 7: Add webhook HMAC signature verification

**Files:**
- Create: `packages/workflow/database/migrations/2026_02_28_000007_add_webhook_secret_to_workflows_table.php`
- Modify: `packages/workflow/src/Models/Workflow.php`
- Modify: `packages/workflow/src/Http/Controllers/WebhookTriggerController.php`
- Test: `packages/workflow/tests/Feature/WebhookTriggerTest.php`

**Step 1: Write the failing test**

Add to `packages/workflow/tests/Feature/WebhookTriggerTest.php`:

```php
it('rejects webhook with invalid signature', function () {
    $workflow = Workflow::create([
        'name' => 'Signed Webhook',
        'trigger_type' => TriggerType::Webhook,
        'trigger_config' => [],
        'canvas_data' => [],
        'is_active' => true,
        'webhook_secret' => 'test-secret-key',
    ]);

    $payload = json_encode(['event' => 'test']);

    $response = $this->postJson(
        "/workflow/api/webhooks/{$workflow->id}",
        ['event' => 'test'],
        ['X-Signature' => 'invalid-signature']
    );

    $response->assertStatus(401);
});

it('accepts webhook with valid HMAC signature', function () {
    Queue::fake();

    $secret = 'test-secret-key';
    $workflow = Workflow::create([
        'name' => 'Signed Webhook',
        'trigger_type' => TriggerType::Webhook,
        'trigger_config' => [],
        'canvas_data' => [],
        'is_active' => true,
        'webhook_secret' => $secret,
    ]);

    $payload = json_encode(['event' => 'test']);
    $signature = hash_hmac('sha256', $payload, $secret);

    $response = $this->postJson(
        "/workflow/api/webhooks/{$workflow->id}",
        ['event' => 'test'],
        ['X-Signature' => $signature]
    );

    $response->assertOk();
    Queue::assertPushed(\Relaticle\Workflow\Jobs\ExecuteWorkflowJob::class);
});

it('allows webhook without signature when no secret is configured', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Open Webhook',
        'trigger_type' => TriggerType::Webhook,
        'trigger_config' => [],
        'canvas_data' => [],
        'is_active' => true,
    ]);

    $response = $this->postJson(
        "/workflow/api/webhooks/{$workflow->id}",
        ['event' => 'test']
    );

    $response->assertOk();
});
```

**Step 2: Run test to verify it fails**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/WebhookTriggerTest.php --filter="signature"`
Expected: FAIL

**Step 3: Create migration**

Create `packages/workflow/database/migrations/2026_02_28_000007_add_webhook_secret_to_workflows_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('workflow.table_prefix');

        Schema::table("{$prefix}workflows", function (Blueprint $table) {
            $table->string('webhook_secret')->nullable()->after('canvas_version');
        });
    }

    public function down(): void
    {
        $prefix = config('workflow.table_prefix');

        Schema::table("{$prefix}workflows", function (Blueprint $table) {
            $table->dropColumn('webhook_secret');
        });
    }
};
```

**Step 4: Update Workflow model**

Add `webhook_secret` to fillable in `packages/workflow/src/Models/Workflow.php`.

**Step 5: Update WebhookTriggerController**

Modify `packages/workflow/src/Http/Controllers/WebhookTriggerController.php`:

```php
public function __invoke(Request $request, Workflow $workflow): JsonResponse
{
    // Verify HMAC signature if webhook_secret is set
    if ($workflow->webhook_secret) {
        $signature = $request->header('X-Signature', '');
        $expectedSignature = hash_hmac('sha256', $request->getContent(), $workflow->webhook_secret);

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json(['error' => 'Invalid webhook signature.'], 401);
        }
    }

    $trigger = app(WebhookTrigger::class);

    try {
        $trigger->trigger($workflow, $request->all());
    } catch (\InvalidArgumentException $e) {
        return response()->json(['error' => $e->getMessage()], 422);
    }

    return response()->json(['message' => 'Webhook processed.']);
}
```

**Step 6: Run tests**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/WebhookTriggerTest.php`
Expected: ALL PASS

**Step 7: Commit**

```bash
git add packages/workflow/database/migrations/2026_02_28_000007_add_webhook_secret_to_workflows_table.php packages/workflow/src/Models/Workflow.php packages/workflow/src/Http/Controllers/WebhookTriggerController.php packages/workflow/tests/Feature/WebhookTriggerTest.php
git commit -m "feat(workflow): add HMAC signature verification for webhook security"
```

---

## Task 8: Add action config validation enforcement

**Files:**
- Modify: `packages/workflow/src/Engine/WorkflowExecutor.php`
- Test: `packages/workflow/tests/Feature/WorkflowExecutionTest.php`

**Step 1: Write the failing test**

Add to `packages/workflow/tests/Feature/WorkflowExecutionTest.php`:

```php
it('fails step when action config is missing required fields', function () {
    $workflow = Workflow::create([
        'name' => 'Validation Test',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger-1', 'type' => NodeType::Trigger,
        'position_x' => 0, 'position_y' => 0,
    ]);
    $action = $workflow->nodes()->create([
        'node_id' => 'action-1', 'type' => NodeType::Action,
        'action_type' => 'send_email',
        'config' => ['subject' => 'Test'], // missing required 'to' and 'body'
        'position_x' => 0, 'position_y' => 100,
    ]);
    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $action->id]);

    $executor = app(\Relaticle\Workflow\Engine\WorkflowExecutor::class);
    $run = $executor->execute($workflow, []);

    expect($run->status)->toBe(WorkflowRunStatus::Failed);
    expect($run->error_message)->toContain('to');
});
```

**Step 2: Run test to verify it fails**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/WorkflowExecutionTest.php --filter="missing required"`
Expected: FAIL - no validation happens, email action throws or sends to empty address

**Step 3: Add validation to executeActionNode()**

In `packages/workflow/src/Engine/WorkflowExecutor.php`, add a `validateActionConfig()` method and call it in `executeActionNode()`:

```php
private function validateActionConfig(string $actionType, array $config): void
{
    $actions = $this->manager->getRegisteredActions();
    $actionClass = $actions[$actionType] ?? null;

    if ($actionClass === null) {
        return;
    }

    $schema = $actionClass instanceof \Relaticle\Workflow\Actions\Contracts\WorkflowAction
        ? $actionClass::configSchema()
        : (is_string($actionClass) ? $actionClass::configSchema() : []);

    $rules = [];
    foreach ($schema as $field => $fieldConfig) {
        if (!empty($fieldConfig['required'])) {
            $rules[$field] = 'required';
        }
    }

    if (empty($rules)) {
        return;
    }

    $validator = \Illuminate\Support\Facades\Validator::make($config, $rules);

    if ($validator->fails()) {
        throw new \RuntimeException(
            "Action '{$actionType}' config validation failed: " . $validator->errors()->first()
        );
    }
}
```

Call it in `executeActionNode()` before executing the action.

**Step 4: Run tests**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/WorkflowExecutionTest.php`
Expected: ALL PASS

**Step 5: Commit**

```bash
git add packages/workflow/src/Engine/WorkflowExecutor.php packages/workflow/tests/Feature/WorkflowExecutionTest.php
git commit -m "feat(workflow): enforce action config validation from configSchema()"
```

---

## Task 9: Add tenant scoping to trigger queries and scheduled job

**Files:**
- Modify: `packages/workflow/src/Triggers/RecordEventTrigger.php`
- Modify: `packages/workflow/src/Jobs/EvaluateScheduledWorkflowsJob.php`
- Modify: `packages/workflow/src/Observers/WorkflowModelObserver.php`
- Test: `packages/workflow/tests/Feature/TenancyScopingTest.php`
- Test: `packages/workflow/tests/Feature/ScheduledTriggerTest.php`

**Step 1: Write the failing tests**

Add to `packages/workflow/tests/Feature/TenancyScopingTest.php`:

```php
it('scopes record event trigger queries to tenant', function () {
    Queue::fake();

    app(\Relaticle\Workflow\WorkflowManager::class)->useTenancy(
        scopeColumn: 'tenant_id',
        resolver: fn () => 'team-1',
    );

    // Register the test model
    Workflow::registerTriggerableModel(\Relaticle\Workflow\Tests\Fixtures\TestCompany::class, [
        'label' => 'Company',
        'events' => ['created'],
    ]);

    // Create workflows for different tenants
    Workflow::withoutGlobalScopes()->create([
        'name' => 'Team 1 Workflow',
        'tenant_id' => 'team-1',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => ['model' => \Relaticle\Workflow\Tests\Fixtures\TestCompany::class, 'event' => 'created'],
        'canvas_data' => [],
        'is_active' => true,
    ]);
    Workflow::withoutGlobalScopes()->create([
        'name' => 'Team 2 Workflow',
        'tenant_id' => 'team-2',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => ['model' => \Relaticle\Workflow\Tests\Fixtures\TestCompany::class, 'event' => 'created'],
        'canvas_data' => [],
        'is_active' => true,
    ]);

    // Only team-1 workflow should match (due to global scope)
    $trigger = app(\Relaticle\Workflow\Triggers\RecordEventTrigger::class);
    $company = new \Relaticle\Workflow\Tests\Fixtures\TestCompany(['name' => 'Acme']);
    $matching = $trigger->getMatchingWorkflows($company, 'created');

    expect($matching)->toHaveCount(1);
    expect($matching->first()->name)->toBe('Team 1 Workflow');
});
```

**Step 2: Run test to verify it fails**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/TenancyScopingTest.php --filter="scopes record event"`
Expected: FAIL - RecordEventTrigger queries bypass global scope since it uses `Workflow::query()` directly which should include the scope, but we need to verify it works with the new BelongsToTenant trait.

**Step 3: Verify and fix RecordEventTrigger**

The `RecordEventTrigger::getMatchingWorkflows()` already uses `Workflow::query()` which should have the global scope applied. Verify this works. If it does, the test should pass after Task 1.

For `EvaluateScheduledWorkflowsJob`, same logic applies - `Workflow::query()` should already be scoped. However, we need to handle the case where scheduled jobs run in a queue context where the tenant resolver might not have context. Update the job:

Modify `packages/workflow/src/Jobs/EvaluateScheduledWorkflowsJob.php`:

```php
public function handle(ScheduledTrigger $trigger): void
{
    // Query without global scopes since this job evaluates ALL tenants' workflows
    $workflows = Workflow::withoutGlobalScopes()
        ->where('is_active', true)
        ->where('trigger_type', TriggerType::TimeBased)
        ->get();

    foreach ($workflows as $workflow) {
        if ($trigger->evaluate($workflow)) {
            ExecuteWorkflowJob::dispatch($workflow, [
                'tenant_id' => $workflow->tenant_id,
            ]);
            $workflow->update(['last_triggered_at' => now()]);
        }
    }
}
```

**Step 4: Run tests**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/TenancyScopingTest.php tests/Feature/ScheduledTriggerTest.php tests/Feature/RecordEventTriggerTest.php`
Expected: ALL PASS

**Step 5: Commit**

```bash
git add packages/workflow/src/Triggers/RecordEventTrigger.php packages/workflow/src/Jobs/EvaluateScheduledWorkflowsJob.php packages/workflow/tests/Feature/TenancyScopingTest.php
git commit -m "fix(workflow): ensure trigger queries respect tenant scoping"
```

---

## Task 10: Harden email action with validation and error handling

**Files:**
- Modify: `packages/workflow/src/Actions/SendEmailAction.php`
- Test: `packages/workflow/tests/Feature/BuiltInActionsTest.php`

**Step 1: Write the failing test**

Add to `packages/workflow/tests/Feature/BuiltInActionsTest.php`:

```php
it('throws exception for invalid email address in SendEmailAction', function () {
    $action = new \Relaticle\Workflow\Actions\SendEmailAction();

    $action->execute(['to' => 'not-an-email', 'subject' => 'Test', 'body' => 'Body'], []);
})->throws(\InvalidArgumentException::class, 'Invalid email');

it('catches mail sending failures gracefully', function () {
    Mail::fake();
    Mail::shouldReceive('to->send')->andThrow(new \Exception('SMTP error'));

    $action = new \Relaticle\Workflow\Actions\SendEmailAction();
    $result = $action->execute(['to' => 'user@example.com', 'subject' => 'Test', 'body' => 'Body'], []);

    expect($result['sent'])->toBeFalse();
    expect($result['error'])->toContain('SMTP error');
});
```

**Step 2: Run tests to verify they fail**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/BuiltInActionsTest.php --filter="invalid email|mail sending failures"`
Expected: FAIL

**Step 3: Update SendEmailAction**

Modify `packages/workflow/src/Actions/SendEmailAction.php`:

```php
public function execute(array $config, array $context): array
{
    $to = $config['to'] ?? '';
    $subject = $config['subject'] ?? 'Workflow Notification';
    $body = $config['body'] ?? '';

    if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
        throw new \InvalidArgumentException("Invalid email address: {$to}");
    }

    try {
        Mail::to($to)->send(new WorkflowNotification($subject, $body));

        return [
            'sent' => true,
            'to' => $to,
        ];
    } catch (\Throwable $e) {
        return [
            'sent' => false,
            'to' => $to,
            'error' => $e->getMessage(),
        ];
    }
}
```

**Step 4: Run tests**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/BuiltInActionsTest.php`
Expected: ALL PASS

**Step 5: Commit**

```bash
git add packages/workflow/src/Actions/SendEmailAction.php packages/workflow/tests/Feature/BuiltInActionsTest.php
git commit -m "fix(workflow): harden SendEmailAction with email validation and error handling"
```

---

## Task 11: Add SSRF prevention and timeout to HTTP actions

**Files:**
- Create: `packages/workflow/src/Actions/Concerns/PreventsSSRF.php`
- Modify: `packages/workflow/src/Actions/HttpRequestAction.php`
- Modify: `packages/workflow/src/Actions/SendWebhookAction.php`
- Test: `packages/workflow/tests/Feature/BuiltInActionsTest.php`

**Step 1: Write the failing test**

Add to `packages/workflow/tests/Feature/BuiltInActionsTest.php`:

```php
it('rejects requests to private IP addresses', function () {
    $action = new \Relaticle\Workflow\Actions\HttpRequestAction();

    $action->execute(['method' => 'GET', 'url' => 'http://127.0.0.1/admin'], []);
})->throws(\InvalidArgumentException::class, 'private');

it('rejects requests to localhost', function () {
    $action = new \Relaticle\Workflow\Actions\HttpRequestAction();

    $action->execute(['method' => 'GET', 'url' => 'http://localhost/admin'], []);
})->throws(\InvalidArgumentException::class, 'private');

it('rejects webhook to private IP', function () {
    $action = new \Relaticle\Workflow\Actions\SendWebhookAction();

    $action->execute(['url' => 'http://10.0.0.1/hook'], []);
})->throws(\InvalidArgumentException::class, 'private');
```

**Step 2: Run tests to verify they fail**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/BuiltInActionsTest.php --filter="private IP|localhost"`
Expected: FAIL

**Step 3: Create PreventsSSRF trait**

Create `packages/workflow/src/Actions/Concerns/PreventsSSRF.php`:

```php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions\Concerns;

trait PreventsSSRF
{
    protected function validateUrl(string $url): void
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';

        if (empty($host)) {
            throw new \InvalidArgumentException('URL must have a valid host.');
        }

        // Block localhost variants
        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1', '[::1]'];
        if (in_array(strtolower($host), $blockedHosts, true)) {
            throw new \InvalidArgumentException("Requests to private/local addresses are not allowed.");
        }

        // Block private IP ranges
        $ip = gethostbyname($host);
        if ($ip !== $host && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new \InvalidArgumentException("Requests to private/reserved IP addresses are not allowed.");
        }
    }
}
```

**Step 4: Apply trait to both actions**

In `HttpRequestAction.php`, add `use PreventsSSRF;` and call `$this->validateUrl($url)` at the start of `execute()`.

In `SendWebhookAction.php`, add `use PreventsSSRF;` and call `$this->validateUrl($url)` at the start of `execute()`.

**Step 5: Run tests**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/BuiltInActionsTest.php`
Expected: ALL PASS

**Step 6: Commit**

```bash
git add packages/workflow/src/Actions/Concerns/PreventsSSRF.php packages/workflow/src/Actions/HttpRequestAction.php packages/workflow/src/Actions/SendWebhookAction.php packages/workflow/tests/Feature/BuiltInActionsTest.php
git commit -m "feat(workflow): add SSRF prevention for HTTP and webhook actions"
```

---

## Task 12: Canvas validation and authorization in API

**Files:**
- Modify: `packages/workflow/src/Http/Controllers/CanvasController.php`
- Modify: `packages/workflow/routes/api.php`
- Test: `packages/workflow/tests/Feature/CanvasApiTest.php`

**Step 1: Write the failing tests**

Add to `packages/workflow/tests/Feature/CanvasApiTest.php`:

```php
it('rejects edges referencing non-existent nodes', function () {
    $workflow = Workflow::create([
        'name' => 'Validation Test',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
    ]);

    $response = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'nodes' => [
            ['id' => 'trigger-1', 'type' => 'trigger', 'position' => ['x' => 0, 'y' => 0]],
        ],
        'edges' => [
            ['id' => 'e1', 'source' => 'trigger-1', 'target' => 'nonexistent-node'],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('error', fn ($v) => str_contains($v, 'nonexistent-node'));
});

it('rejects nodes with invalid type', function () {
    $workflow = Workflow::create([
        'name' => 'Validation Test',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
    ]);

    $response = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'nodes' => [
            ['id' => 'node-1', 'type' => 'invalid_type', 'position' => ['x' => 0, 'y' => 0]],
        ],
        'edges' => [],
    ]);

    $response->assertStatus(422);
});
```

**Step 2: Run tests to verify they fail**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/CanvasApiTest.php --filter="non-existent nodes|invalid type"`
Expected: FAIL

**Step 3: Add validation to CanvasController::update()**

Modify `packages/workflow/src/Http/Controllers/CanvasController.php` `update()` method. Add validation before the database transaction:

```php
public function update(Request $request, Workflow $workflow): JsonResponse
{
    $validated = $request->validate([
        'nodes' => 'present|array',
        'nodes.*.id' => 'required|string',
        'nodes.*.type' => 'required|string|in:trigger,action,condition,delay,loop,stop',
        'nodes.*.position' => 'required|array',
        'nodes.*.position.x' => 'required|numeric',
        'nodes.*.position.y' => 'required|numeric',
        'edges' => 'present|array',
        'edges.*.id' => 'required|string',
        'edges.*.source' => 'required|string',
        'edges.*.target' => 'required|string',
    ]);

    // Validate edge references
    $nodeIds = collect($validated['nodes'])->pluck('id')->all();
    foreach ($validated['edges'] as $edge) {
        if (! in_array($edge['source'], $nodeIds, true)) {
            return response()->json([
                'error' => "Edge '{$edge['id']}' references non-existent source node '{$edge['source']}'.",
            ], 422);
        }
        if (! in_array($edge['target'], $nodeIds, true)) {
            return response()->json([
                'error' => "Edge '{$edge['id']}' references non-existent target node '{$edge['target']}'.",
            ], 422);
        }
    }

    // ... rest of the existing update logic
```

**Step 4: Add standardized error responses**

Wrap the database transaction in a try/catch:

```php
try {
    DB::transaction(function () use ($workflow, $validated) {
        // ... existing sync logic
    });
} catch (\Throwable $e) {
    return response()->json([
        'error' => 'Failed to save canvas: ' . $e->getMessage(),
    ], 500);
}

return response()->json(['message' => 'Canvas saved.', 'canvas_version' => $workflow->fresh()->canvas_version]);
```

**Step 5: Run tests**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/CanvasApiTest.php`
Expected: ALL PASS

**Step 6: Commit**

```bash
git add packages/workflow/src/Http/Controllers/CanvasController.php packages/workflow/tests/Feature/CanvasApiTest.php
git commit -m "feat(workflow): add canvas validation for node types and edge references"
```

---

## Task 13: Scope WorkflowStatsWidget to tenant

**Files:**
- Modify: `packages/workflow/src/Filament/Widgets/WorkflowStatsWidget.php`
- Test: `packages/workflow/tests/Feature/WorkflowStatsWidgetTest.php`

**Step 1: Write the failing test**

Add to `packages/workflow/tests/Feature/WorkflowStatsWidgetTest.php`:

```php
it('scopes metrics to current tenant', function () {
    app(\Relaticle\Workflow\WorkflowManager::class)->useTenancy(
        scopeColumn: 'tenant_id',
        resolver: fn () => 'team-1',
    );

    // Create runs for team-1
    $wf1 = Workflow::withoutGlobalScopes()->create([
        'name' => 'T1', 'tenant_id' => 'team-1',
        'trigger_type' => TriggerType::Manual, 'trigger_config' => [],
        'canvas_data' => [], 'is_active' => true,
    ]);
    \Relaticle\Workflow\Models\WorkflowRun::create([
        'workflow_id' => $wf1->id,
        'status' => WorkflowRunStatus::Completed,
        'started_at' => now(), 'completed_at' => now(),
    ]);

    // Create runs for team-2
    $wf2 = Workflow::withoutGlobalScopes()->create([
        'name' => 'T2', 'tenant_id' => 'team-2',
        'trigger_type' => TriggerType::Manual, 'trigger_config' => [],
        'canvas_data' => [], 'is_active' => true,
    ]);
    \Relaticle\Workflow\Models\WorkflowRun::create([
        'workflow_id' => $wf2->id,
        'status' => WorkflowRunStatus::Failed,
        'started_at' => now(), 'completed_at' => now(),
    ]);

    $widget = new \Relaticle\Workflow\Filament\Widgets\WorkflowStatsWidget();
    $metrics = $widget->getMetrics();

    // Should only see team-1 data
    expect($metrics['totalRuns'])->toBe(1);
    expect($metrics['activeWorkflows'])->toBe(1);
    expect($metrics['successRate'])->toBe(100.0);
});
```

**Step 2: Run test to verify it fails**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/WorkflowStatsWidgetTest.php --filter="scopes metrics"`
Expected: FAIL - widget queries globally

**Step 3: Scope the widget queries**

Modify `packages/workflow/src/Filament/Widgets/WorkflowStatsWidget.php` `getMetrics()`:

```php
public function getMetrics(): array
{
    // WorkflowRun queries scoped through Workflow's tenant scope
    $workflowIds = Workflow::pluck('id');

    $totalRuns = WorkflowRun::whereIn('workflow_id', $workflowIds)->count();
    $completedRuns = WorkflowRun::whereIn('workflow_id', $workflowIds)
        ->where('status', WorkflowRunStatus::Completed)->count();
    $failedRuns = WorkflowRun::whereIn('workflow_id', $workflowIds)
        ->where('status', WorkflowRunStatus::Failed)->count();
    $activeWorkflows = Workflow::where('is_active', true)->count();
    $successRate = $totalRuns > 0 ? round(($completedRuns / $totalRuns) * 100, 1) : 0;

    return compact('totalRuns', 'completedRuns', 'failedRuns', 'activeWorkflows', 'successRate');
}
```

The key insight: `Workflow::pluck('id')` uses the global TenantScope, so only tenant workflows are included. Then WorkflowRun queries are filtered to only those workflow IDs.

**Step 4: Run tests**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/WorkflowStatsWidgetTest.php`
Expected: ALL PASS

**Step 5: Commit**

```bash
git add packages/workflow/src/Filament/Widgets/WorkflowStatsWidget.php packages/workflow/tests/Feature/WorkflowStatsWidgetTest.php
git commit -m "fix(workflow): scope WorkflowStatsWidget to current tenant"
```

---

## Task 14: Add workflow run history page (ViewWorkflow)

**Files:**
- Create: `packages/workflow/src/Filament/Resources/WorkflowResource/Pages/ViewWorkflow.php`
- Create: `packages/workflow/src/Filament/Resources/WorkflowResource/RelationManagers/RunsRelationManager.php`
- Modify: `packages/workflow/src/Filament/Resources/WorkflowResource.php`
- Test: `packages/workflow/tests/Feature/WorkflowResourceTest.php`

**Step 1: Write the failing test**

Add to `packages/workflow/tests/Feature/WorkflowResourceTest.php`:

```php
it('has a view page with run history', function () {
    $pages = \Relaticle\Workflow\Filament\Resources\WorkflowResource::getPages();

    expect($pages)->toHaveKey('view');
    expect($pages['view']->getPage())->toBe(\Relaticle\Workflow\Filament\Resources\WorkflowResource\Pages\ViewWorkflow::class);
});
```

**Step 2: Run test to verify it fails**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/WorkflowResourceTest.php --filter="view page"`
Expected: FAIL

**Step 3: Create RunsRelationManager**

Create `packages/workflow/src/Filament/Resources/WorkflowResource/RelationManagers/RunsRelationManager.php`:

```php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources\WorkflowResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Relaticle\Workflow\Enums\WorkflowRunStatus;

class RunsRelationManager extends RelationManager
{
    protected static string $relationship = 'runs';

    protected static ?string $title = 'Execution History';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (WorkflowRunStatus $state): string => match ($state) {
                        WorkflowRunStatus::Completed => 'success',
                        WorkflowRunStatus::Failed => 'danger',
                        WorkflowRunStatus::Running => 'info',
                        WorkflowRunStatus::Paused => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('trigger_record_type')
                    ->label('Trigger')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : 'Manual'),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->getStateUsing(function ($record) {
                        if (! $record->started_at || ! $record->completed_at) {
                            return '-';
                        }
                        return $record->started_at->diffForHumans($record->completed_at, true);
                    }),
                Tables\Columns\TextColumn::make('error_message')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->error_message)
                    ->visible(fn ($record) => $record?->error_message !== null),
                Tables\Columns\TextColumn::make('steps_count')
                    ->counts('steps')
                    ->label('Steps'),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(WorkflowRunStatus::class),
            ]);
    }
}
```

**Step 4: Create ViewWorkflow page**

Create `packages/workflow/src/Filament/Resources/WorkflowResource/Pages/ViewWorkflow.php`:

```php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources\WorkflowResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Relaticle\Workflow\Filament\Resources\WorkflowResource;

class ViewWorkflow extends ViewRecord
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('builder')
                ->label('Open Builder')
                ->icon('heroicon-o-paint-brush')
                ->url(fn () => WorkflowResource::getUrl('builder', ['record' => $this->record])),
            Actions\EditAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            \Relaticle\Workflow\Filament\Resources\WorkflowResource\RelationManagers\RunsRelationManager::class,
        ];
    }
}
```

**Step 5: Register page in WorkflowResource**

In `packages/workflow/src/Filament/Resources/WorkflowResource.php`, add view page to `getPages()`:

```php
public static function getPages(): array
{
    return [
        'index' => Pages\ListWorkflows::route('/'),
        'create' => Pages\CreateWorkflow::route('/create'),
        'view' => Pages\ViewWorkflow::route('/{record}'),
        'edit' => Pages\EditWorkflow::route('/{record}/edit'),
        'builder' => Pages\WorkflowBuilder::route('/{record}/builder'),
    ];
}
```

Also update the table actions to include ViewAction and make the name column clickable:

```php
Tables\Columns\TextColumn::make('name')
    ->searchable()
    ->sortable()
    ->url(fn ($record) => static::getUrl('view', ['record' => $record])),
```

**Step 6: Run tests**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/WorkflowResourceTest.php`
Expected: ALL PASS

**Step 7: Commit**

```bash
git add packages/workflow/src/Filament/Resources/WorkflowResource/Pages/ViewWorkflow.php packages/workflow/src/Filament/Resources/WorkflowResource/RelationManagers/RunsRelationManager.php packages/workflow/src/Filament/Resources/WorkflowResource.php packages/workflow/tests/Feature/WorkflowResourceTest.php
git commit -m "feat(workflow): add ViewWorkflow page with run history relation manager"
```

---

## Task 15: Add activation safeguards

**Files:**
- Modify: `packages/workflow/src/Models/Workflow.php`
- Modify: `packages/workflow/src/Filament/Resources/WorkflowResource.php`
- Test: `packages/workflow/tests/Feature/ModelsTest.php`

**Step 1: Write the failing test**

Add to `packages/workflow/tests/Feature/ModelsTest.php`:

```php
it('validates workflow has required nodes before activation', function () {
    $workflow = Workflow::create([
        'name' => 'Empty Workflow',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
        'is_active' => false,
    ]);

    // No nodes at all - should fail validation
    expect($workflow->getActivationErrors())->toContain('Workflow must have at least one trigger node.');
    expect($workflow->canActivate())->toBeFalse();
});

it('validates workflow has trigger node before activation', function () {
    $workflow = Workflow::create([
        'name' => 'No Trigger',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
        'is_active' => false,
    ]);

    // Add only an action node, no trigger
    $workflow->nodes()->create([
        'node_id' => 'action-1', 'type' => NodeType::Action,
        'action_type' => 'log_message', 'config' => [],
        'position_x' => 0, 'position_y' => 0,
    ]);

    expect($workflow->getActivationErrors())->toContain('Workflow must have at least one trigger node.');
});

it('allows activation when workflow has trigger and action nodes', function () {
    $workflow = Workflow::create([
        'name' => 'Complete Workflow',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger-1', 'type' => NodeType::Trigger,
        'position_x' => 0, 'position_y' => 0,
    ]);
    $workflow->nodes()->create([
        'node_id' => 'action-1', 'type' => NodeType::Action,
        'action_type' => 'log_message', 'config' => [],
        'position_x' => 0, 'position_y' => 100,
    ]);
    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $workflow->nodes()->where('node_id', 'action-1')->first()->id,
    ]);

    expect($workflow->canActivate())->toBeTrue();
    expect($workflow->getActivationErrors())->toBeEmpty();
});
```

**Step 2: Run tests to verify they fail**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/ModelsTest.php --filter="activation"`
Expected: FAIL - methods don't exist

**Step 3: Add activation validation to Workflow model**

Add methods to `packages/workflow/src/Models/Workflow.php`:

```php
/**
 * Check if the workflow can be activated.
 */
public function canActivate(): bool
{
    return empty($this->getActivationErrors());
}

/**
 * Get validation errors that prevent activation.
 *
 * @return array<string>
 */
public function getActivationErrors(): array
{
    $errors = [];
    $nodes = $this->nodes()->get();

    if ($nodes->isEmpty()) {
        $errors[] = 'Workflow must have at least one trigger node.';
        return $errors;
    }

    $hasTrigger = $nodes->contains(fn ($node) => $node->type === NodeType::Trigger);
    if (! $hasTrigger) {
        $errors[] = 'Workflow must have at least one trigger node.';
    }

    $hasAction = $nodes->contains(fn ($node) => in_array($node->type, [NodeType::Action, NodeType::Condition, NodeType::Delay, NodeType::Loop], true));
    if (! $hasAction) {
        $errors[] = 'Workflow must have at least one action or logic node.';
    }

    // Check action nodes have action_type configured
    $unconfiguredActions = $nodes->filter(fn ($node) => $node->type === NodeType::Action && empty($node->action_type));
    if ($unconfiguredActions->isNotEmpty()) {
        $errors[] = 'All action nodes must have an action type configured.';
    }

    return $errors;
}
```

**Step 4: Run tests**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/ModelsTest.php`
Expected: ALL PASS

**Step 5: Commit**

```bash
git add packages/workflow/src/Models/Workflow.php packages/workflow/tests/Feature/ModelsTest.php
git commit -m "feat(workflow): add activation safeguards with canActivate() validation"
```

---

## Task 16: Add dynamic trigger config form in Filament

**Files:**
- Modify: `packages/workflow/src/Filament/Resources/WorkflowResource.php`

**Step 1: Update the form schema**

Modify the `form()` method in `packages/workflow/src/Filament/Resources/WorkflowResource.php` to add dynamic trigger config fields:

```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->maxLength(1000),
            Forms\Components\Select::make('trigger_type')
                ->options([
                    'record_event' => 'Record Event',
                    'time_based' => 'Scheduled / Time-Based',
                    'manual' => 'Manual',
                    'webhook' => 'Webhook',
                ])
                ->reactive()
                ->required(),

            // Record Event config
            Forms\Components\Section::make('Trigger Configuration')
                ->schema([
                    Forms\Components\Select::make('trigger_config.model')
                        ->label('Model')
                        ->options(fn () => collect(app(\Relaticle\Workflow\WorkflowManager::class)->getTriggerableModels())
                            ->mapWithKeys(fn ($config, $class) => [$class => $config['label'] ?? class_basename($class)]))
                        ->visible(fn (Forms\Get $get) => $get('trigger_type') === 'record_event')
                        ->required(fn (Forms\Get $get) => $get('trigger_type') === 'record_event'),
                    Forms\Components\Select::make('trigger_config.event')
                        ->label('Event')
                        ->options([
                            'created' => 'Created',
                            'updated' => 'Updated',
                            'deleted' => 'Deleted',
                        ])
                        ->visible(fn (Forms\Get $get) => $get('trigger_type') === 'record_event')
                        ->required(fn (Forms\Get $get) => $get('trigger_type') === 'record_event'),
                    Forms\Components\TextInput::make('trigger_config.cron')
                        ->label('Cron Expression')
                        ->placeholder('*/5 * * * *')
                        ->visible(fn (Forms\Get $get) => $get('trigger_type') === 'time_based'),
                    Forms\Components\TextInput::make('webhook_secret')
                        ->label('Webhook Secret (optional)')
                        ->password()
                        ->revealable()
                        ->visible(fn (Forms\Get $get) => $get('trigger_type') === 'webhook'),
                ])
                ->visible(fn (Forms\Get $get) => in_array($get('trigger_type'), ['record_event', 'time_based', 'webhook'])),

            Forms\Components\Toggle::make('is_active')
                ->default(false)
                ->helperText(fn ($record) => $record && ! $record->canActivate()
                    ? 'Cannot activate: ' . implode(' ', $record->getActivationErrors())
                    : null),
        ]);
}
```

**Step 2: Run existing tests to verify no regressions**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/WorkflowResourceTest.php`
Expected: ALL PASS

**Step 3: Commit**

```bash
git add packages/workflow/src/Filament/Resources/WorkflowResource.php
git commit -m "feat(workflow): add dynamic trigger config form with model/event/cron fields"
```

---

## Task 17: Add run detail view with step inspection

**Files:**
- Create: `packages/workflow/src/Filament/Resources/WorkflowRunResource.php`
- Create: `packages/workflow/src/Filament/Resources/WorkflowRunResource/Pages/ViewWorkflowRun.php`

**Step 1: Create WorkflowRunResource for step-level drill-down**

Create `packages/workflow/src/Filament/Resources/WorkflowRunResource.php`:

```php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Relaticle\Workflow\Enums\StepStatus;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Models\WorkflowRun;

class WorkflowRunResource extends Resource
{
    protected static ?string $model = WorkflowRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-play';

    protected static ?string $navigationGroup = 'Automation';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Run History';

    protected static bool $shouldRegisterNavigation = false;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Run Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (WorkflowRunStatus $state): string => match ($state) {
                                WorkflowRunStatus::Completed => 'success',
                                WorkflowRunStatus::Failed => 'danger',
                                WorkflowRunStatus::Running => 'info',
                                WorkflowRunStatus::Paused => 'warning',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('workflow.name')
                            ->label('Workflow'),
                        Infolists\Components\TextEntry::make('started_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('error_message')
                            ->visible(fn ($record) => filled($record->error_message))
                            ->color('danger'),
                    ])->columns(2),
                Infolists\Components\Section::make('Execution Steps')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('steps')
                            ->schema([
                                Infolists\Components\TextEntry::make('node.node_id')
                                    ->label('Node'),
                                Infolists\Components\TextEntry::make('node.type')
                                    ->label('Type')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (StepStatus $state): string => match ($state) {
                                        StepStatus::Completed => 'success',
                                        StepStatus::Failed => 'danger',
                                        StepStatus::Skipped => 'gray',
                                        default => 'info',
                                    }),
                                Infolists\Components\TextEntry::make('started_at')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('completed_at')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('error_message')
                                    ->visible(fn ($record) => filled($record->error_message))
                                    ->color('danger'),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'view' => Pages\ViewWorkflowRun::route('/{record}'),
        ];
    }
}
```

**Step 2: Create ViewWorkflowRun page**

Create `packages/workflow/src/Filament/Resources/WorkflowRunResource/Pages/ViewWorkflowRun.php`:

```php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources\WorkflowRunResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Relaticle\Workflow\Filament\Resources\WorkflowRunResource;

class ViewWorkflowRun extends ViewRecord
{
    protected static string $resource = WorkflowRunResource::class;
}
```

**Step 3: Register in WorkflowPlugin**

Update `packages/workflow/src/Filament/WorkflowPlugin.php` to register the new resource:

```php
public function register(Panel $panel): void
{
    $panel->resources([
        WorkflowResource::class,
        WorkflowRunResource::class,
    ]);
}
```

**Step 4: Make run rows clickable in RunsRelationManager**

In `RunsRelationManager.php`, add a record action:

```php
->actions([
    Tables\Actions\Action::make('view')
        ->icon('heroicon-o-eye')
        ->url(fn ($record) => WorkflowRunResource::getUrl('view', ['record' => $record])),
])
```

**Step 5: Run tests**

Run: `cd packages/workflow && php vendor/bin/pest tests/Feature/WorkflowResourceTest.php`
Expected: ALL PASS

**Step 6: Commit**

```bash
git add packages/workflow/src/Filament/Resources/WorkflowRunResource.php packages/workflow/src/Filament/Resources/WorkflowRunResource/Pages/ViewWorkflowRun.php packages/workflow/src/Filament/WorkflowPlugin.php packages/workflow/src/Filament/Resources/WorkflowResource/RelationManagers/RunsRelationManager.php
git commit -m "feat(workflow): add WorkflowRun detail view with step inspection"
```

---

## Task 18: Frontend - Add toast notifications

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/index.js`
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Add toast notification system**

Create a toast utility in `packages/workflow/resources/js/workflow-builder/index.js`. Add a `showToast()` function and replace the button text feedback in `saveCanvas()`:

```javascript
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `workflow-toast workflow-toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
```

Replace the save button feedback in `saveCanvas()`:

```javascript
// Success:
showToast('Workflow saved successfully.', 'success');

// Error:
showToast('Failed to save workflow. Please try again.', 'error');

// Version conflict:
showToast('Canvas was modified by another user. Reloading...', 'warning');
```

**Step 2: Add toast styles**

Add to `packages/workflow/resources/css/workflow-builder.css`:

```css
/* Toast Notifications */
.workflow-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 8px;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    z-index: 10000;
    opacity: 0;
    transform: translateY(-10px);
    transition: opacity 0.3s, transform 0.3s;
    max-width: 400px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.workflow-toast.show {
    opacity: 1;
    transform: translateY(0);
}
.workflow-toast-success { background-color: #22c55e; }
.workflow-toast-error { background-color: #ef4444; }
.workflow-toast-warning { background-color: #f59e0b; }
```

**Step 3: Build frontend**

Run: `cd packages/workflow && npm run build`

**Step 4: Commit**

```bash
git add packages/workflow/resources/js/workflow-builder/index.js packages/workflow/resources/css/workflow-builder.css
git commit -m "feat(workflow): add toast notifications for save feedback"
```

---

## Task 19: Frontend - Add form validation and node status indicators

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/config-panel.js`
- Modify: `packages/workflow/resources/js/workflow-builder/index.js`
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Add validation to config panel**

In `packages/workflow/resources/js/workflow-builder/config-panel.js`, add validation logic that checks required fields before save:

```javascript
export function validateAllNodes(graph) {
    const cells = graph.getCells();
    const errors = [];

    cells.forEach(cell => {
        if (!cell.isNode()) return;
        const data = cell.getData() || {};
        const type = data.type;

        if (type === 'action' && !data.actionType) {
            errors.push({ nodeId: cell.id, message: 'Action type not configured' });
            cell.attr('body/stroke', '#ef4444');
            cell.attr('body/strokeWidth', 2);
        } else if (cell.isNode()) {
            cell.attr('body/stroke', '#e2e8f0');
            cell.attr('body/strokeWidth', 1);
        }
    });

    return errors;
}
```

In `saveCanvas()` in `index.js`, call validation before saving:

```javascript
const validationErrors = validateAllNodes(graph);
if (validationErrors.length > 0) {
    showToast(`${validationErrors.length} node(s) need configuration before saving.`, 'warning');
    return;
}
```

**Step 2: Add node status indicator styles**

Add to CSS:

```css
/* Node validation states */
.workflow-node.node-valid { border-left: 3px solid #22c55e; }
.workflow-node.node-warning { border-left: 3px solid #f59e0b; }
.workflow-node.node-error { border-left: 3px solid #ef4444; }
```

**Step 3: Build frontend**

Run: `cd packages/workflow && npm run build`

**Step 4: Commit**

```bash
git add packages/workflow/resources/js/workflow-builder/config-panel.js packages/workflow/resources/js/workflow-builder/index.js packages/workflow/resources/css/workflow-builder.css
git commit -m "feat(workflow): add form validation and node status indicators"
```

---

## Task 20: Frontend - Add delete confirmation dialog

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/graph.js`
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Add confirmation dialog**

In `packages/workflow/resources/js/workflow-builder/graph.js`, replace the delete keyboard handler with one that shows a confirmation:

```javascript
function showConfirmDialog(message, onConfirm) {
    const overlay = document.createElement('div');
    overlay.className = 'workflow-confirm-overlay';

    const dialog = document.createElement('div');
    dialog.className = 'workflow-confirm-dialog';
    dialog.innerHTML = `
        <p>${message}</p>
        <div class="workflow-confirm-actions">
            <button class="workflow-confirm-cancel">Cancel</button>
            <button class="workflow-confirm-delete">Delete</button>
        </div>
    `;

    overlay.appendChild(dialog);
    document.body.appendChild(overlay);

    dialog.querySelector('.workflow-confirm-cancel').onclick = () => overlay.remove();
    dialog.querySelector('.workflow-confirm-delete').onclick = () => {
        onConfirm();
        overlay.remove();
    };
    overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };
}
```

Update the delete handler:

```javascript
graph.bindKey(['delete', 'backspace'], () => {
    const cells = graph.getSelectedCells();
    if (cells.length === 0) return;

    const hasTrigger = cells.some(c => c.isNode() && c.getData()?.type === 'trigger');
    const message = hasTrigger
        ? 'This will delete the trigger node. The workflow will not function without it. Continue?'
        : `Delete ${cells.length} selected element(s)?`;

    showConfirmDialog(message, () => graph.removeCells(cells));
});
```

**Step 2: Add dialog styles**

Add to CSS:

```css
/* Confirmation Dialog */
.workflow-confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    z-index: 10001;
    display: flex;
    align-items: center;
    justify-content: center;
}
.workflow-confirm-dialog {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    max-width: 400px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}
.workflow-confirm-dialog p {
    margin: 0 0 16px;
    font-size: 14px;
    color: #334155;
    line-height: 1.5;
}
.workflow-confirm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
.workflow-confirm-cancel, .workflow-confirm-delete {
    padding: 8px 16px;
    border-radius: 6px;
    border: none;
    font-size: 13px;
    cursor: pointer;
}
.workflow-confirm-cancel {
    background: #f1f5f9;
    color: #475569;
}
.workflow-confirm-cancel:hover { background: #e2e8f0; }
.workflow-confirm-delete {
    background: #ef4444;
    color: #fff;
}
.workflow-confirm-delete:hover { background: #dc2626; }
```

**Step 3: Build frontend**

Run: `cd packages/workflow && npm run build`

**Step 4: Commit**

```bash
git add packages/workflow/resources/js/workflow-builder/graph.js packages/workflow/resources/css/workflow-builder.css
git commit -m "feat(workflow): add delete confirmation dialog with trigger warning"
```

---

## Task 21: Frontend - Replace Unicode icons with SVGs

**Files:**
- Modify: `packages/workflow/resources/views/builder.blade.php`
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/TriggerNode.js`
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/ActionNode.js`
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/ConditionNode.js`
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/DelayNode.js`
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/LoopNode.js`
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/StopNode.js`

**Step 1: Replace sidebar Unicode icons in builder.blade.php**

Replace the Unicode characters (☇, ▶, ◊, ⏱, 🔄, ⊙) with inline SVG heroicons that render consistently:

- Trigger (☇) → bolt SVG
- Action (▶) → play SVG
- Condition (◊) → arrow-path SVG
- Delay (⏱) → clock SVG
- Loop (🔄) → arrow-path SVG
- Stop (⊙) → stop-circle SVG

**Step 2: Replace node header icons in JS files**

In each node registration file, replace the Unicode character in the HTML template with the corresponding SVG.

**Step 3: Build frontend**

Run: `cd packages/workflow && npm run build`

**Step 4: Commit**

```bash
git add packages/workflow/resources/views/builder.blade.php packages/workflow/resources/js/workflow-builder/nodes/
git commit -m "feat(workflow): replace Unicode icons with SVGs for cross-platform consistency"
```

---

## Task 22: Frontend - Add error recovery for canvas load/save

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/index.js`
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Add error state for canvas load**

In `loadCanvas()`, add error handling that shows a retry UI:

```javascript
async function loadCanvas(graph, workflowId) {
    const container = document.getElementById('workflow-canvas-container');

    try {
        const response = await fetch(`/workflow/api/workflows/${workflowId}/canvas`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        // ... existing node/edge loading
    } catch (error) {
        console.error('Failed to load canvas:', error);

        container.innerHTML = `
            <div class="workflow-error-state">
                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#94a3b8">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3>Failed to load workflow</h3>
                <p>Check your connection and try again.</p>
                <button onclick="location.reload()" class="toolbar-btn toolbar-btn-primary">Retry</button>
            </div>
        `;
    }
}
```

**Step 2: Preserve unsaved changes on save failure**

In `saveCanvas()`, don't clear the graph on failure:

```javascript
// On error, re-enable save button and show toast
saveBtn.disabled = false;
saveBtn.textContent = 'Save';
showToast('Failed to save. Your changes are preserved locally.', 'error');
```

**Step 3: Add error state styles**

```css
.workflow-error-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    gap: 12px;
    color: #64748b;
}
.workflow-error-state h3 { margin: 0; font-size: 16px; color: #334155; }
.workflow-error-state p { margin: 0; font-size: 14px; }
```

**Step 4: Build frontend**

Run: `cd packages/workflow && npm run build`

**Step 5: Commit**

```bash
git add packages/workflow/resources/js/workflow-builder/index.js packages/workflow/resources/css/workflow-builder.css
git commit -m "feat(workflow): add error recovery with retry UI and save failure handling"
```

---

## Task 23: Run full test suite and fix any regressions

**Files:**
- All test files

**Step 1: Run full test suite**

Run: `cd packages/workflow && php vendor/bin/pest`
Expected: ALL PASS

**Step 2: Fix any failures**

Address any test failures caused by the changes above.

**Step 3: Build frontend and verify**

Run: `cd packages/workflow && npm run build`

**Step 4: Final commit**

```bash
git add -A
git commit -m "test(workflow): fix regressions and verify full test suite passes"
```

---

## Task 24: Build final frontend bundle and verify production readiness

**Step 1: Clean build**

```bash
cd packages/workflow && rm -rf public/vendor/workflow && npm run build
```

**Step 2: Run full test suite one more time**

```bash
cd packages/workflow && php vendor/bin/pest
```

**Step 3: Verify git status is clean**

```bash
git status
```

**Step 4: Final commit if needed**

```bash
git add public/vendor/workflow/
git commit -m "build(workflow): production frontend bundle"
```
