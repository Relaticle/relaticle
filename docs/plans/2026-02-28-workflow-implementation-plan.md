# Workflow Automation Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build `relaticle/workflow` — a general-purpose Laravel package for visual workflow automation with AntV X6 canvas, async execution, and optional Filament adapter.

**Architecture:** Single Composer package using `spatie/laravel-package-tools`. Core engine (models, executor, triggers, actions) works with any Laravel app. Filament integration loads conditionally. X6 canvas assets bundled via Vite. TDD throughout using Pest + Orchestra Testbench + Pest Browser Plugin (Playwright).

**Tech Stack:** PHP 8.3+, Laravel 12, Filament 5, Pest 4, AntV X6 v2, Vite 7, Playwright, PostgreSQL

**Design doc:** `docs/plans/2026-02-28-workflow-automation-design.md`

---

## Phase 1: Package Scaffold & Foundation

### Task 1: Initialize Package Repository

**Files:**
- Create: `packages/workflow/composer.json`
- Create: `packages/workflow/phpunit.xml`
- Create: `packages/workflow/testbench.yaml`
- Create: `packages/workflow/tests/Pest.php`
- Create: `packages/workflow/tests/TestCase.php`
- Create: `packages/workflow/src/WorkflowServiceProvider.php`
- Create: `packages/workflow/config/workflow.php`
- Modify: `/home/forge/projects/relaticle/composer.json` (add path repository)

**Step 1: Create package directory and composer.json**

```bash
mkdir -p packages/workflow/src packages/workflow/tests/Feature packages/workflow/tests/Browser packages/workflow/config packages/workflow/database/migrations packages/workflow/resources/js packages/workflow/resources/css packages/workflow/resources/views packages/workflow/routes
```

```json
// packages/workflow/composer.json
{
    "name": "relaticle/workflow",
    "description": "Visual workflow automation engine for Laravel with optional Filament adapter",
    "keywords": ["laravel", "workflow", "automation", "filament", "visual-builder"],
    "license": "MIT",
    "authors": [
        {"name": "Relaticle", "email": "hello@relaticle.com"}
    ],
    "require": {
        "php": "^8.3",
        "illuminate/contracts": "^11.0||^12.0",
        "illuminate/support": "^11.0||^12.0",
        "spatie/laravel-package-tools": "^1.15"
    },
    "require-dev": {
        "orchestra/testbench": "^10.0",
        "pestphp/pest": "^4.0",
        "pestphp/pest-plugin-laravel": "^4.0",
        "pestphp/pest-plugin-browser": "^4.0",
        "filament/filament": "^5.0",
        "larastan/larastan": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "Relaticle\\Workflow\\": "src/",
            "Relaticle\\Workflow\\Database\\Factories\\": "database/factories/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Relaticle\\Workflow\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Relaticle\\Workflow\\WorkflowServiceProvider"
            ],
            "aliases": {
                "Workflow": "Relaticle\\Workflow\\Facades\\Workflow"
            }
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    }
}
```

**Step 2: Create minimal ServiceProvider using spatie/laravel-package-tools**

```php
// packages/workflow/src/WorkflowServiceProvider.php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WorkflowServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('workflow')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(WorkflowManager::class);
    }
}
```

```php
// packages/workflow/src/WorkflowManager.php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow;

class WorkflowManager
{
    //
}
```

```php
// packages/workflow/config/workflow.php
<?php

declare(strict_types=1);

return [
    'queue' => env('WORKFLOW_QUEUE', 'default'),
    'table_prefix' => env('WORKFLOW_TABLE_PREFIX', ''),
    'max_steps_per_run' => env('WORKFLOW_MAX_STEPS', 100),
    'max_loop_iterations' => env('WORKFLOW_MAX_LOOP', 500),
    'retry_attempts' => env('WORKFLOW_RETRY_ATTEMPTS', 3),
    'enable_audit_trail' => true,
];
```

**Step 3: Create test infrastructure**

```php
// packages/workflow/tests/TestCase.php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Relaticle\Workflow\WorkflowServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            WorkflowServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
```

```php
// packages/workflow/tests/Pest.php
<?php

declare(strict_types=1);

use Relaticle\Workflow\Tests\TestCase;

uses(TestCase::class)->in('Feature');
```

```xml
<!-- packages/workflow/phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/12.0/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         failOnWarning="true"
         failOnRisky="true"
         failOnEmptyTestSuite="true">
    <testsuites>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Browser">
            <directory>tests/Browser</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

```yaml
# packages/workflow/testbench.yaml
providers:
  - Relaticle\Workflow\WorkflowServiceProvider
```

**Step 4: Add path repository to Relaticle's composer.json**

Add to the host app's `composer.json`:
```json
"repositories": [
    {
        "type": "path",
        "url": "packages/workflow"
    }
]
```

Then: `composer require relaticle/workflow:@dev`

**Step 5: Verify the package loads**

Run: `cd packages/workflow && composer install && vendor/bin/pest`
Expected: 0 tests, 0 assertions (no tests yet, but no errors)

**Step 6: Commit**

```bash
git add packages/workflow
git commit -m "feat(workflow): scaffold package with service provider and test infrastructure"
```

---

### Task 2: Enums

**Files:**
- Create: `packages/workflow/src/Enums/TriggerType.php`
- Create: `packages/workflow/src/Enums/NodeType.php`
- Create: `packages/workflow/src/Enums/WorkflowRunStatus.php`
- Create: `packages/workflow/src/Enums/StepStatus.php`

**Step 1: Create all enum files**

```php
// packages/workflow/src/Enums/TriggerType.php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Enums;

enum TriggerType: string
{
    case RecordEvent = 'record_event';
    case TimeBased = 'time_based';
    case Manual = 'manual';
    case Webhook = 'webhook';
}
```

```php
// packages/workflow/src/Enums/NodeType.php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Enums;

enum NodeType: string
{
    case Trigger = 'trigger';
    case Action = 'action';
    case Condition = 'condition';
    case Delay = 'delay';
    case Loop = 'loop';
    case Stop = 'stop';
}
```

```php
// packages/workflow/src/Enums/WorkflowRunStatus.php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Enums;

enum WorkflowRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
```

```php
// packages/workflow/src/Enums/StepStatus.php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Enums;

enum StepStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
```

**Step 2: Commit**

```bash
git add packages/workflow/src/Enums
git commit -m "feat(workflow): add TriggerType, NodeType, WorkflowRunStatus, StepStatus enums"
```

---

### Task 3: Database Migrations

**Files:**
- Create: `packages/workflow/database/migrations/2026_02_28_000001_create_workflows_table.php`
- Create: `packages/workflow/database/migrations/2026_02_28_000002_create_workflow_nodes_table.php`
- Create: `packages/workflow/database/migrations/2026_02_28_000003_create_workflow_edges_table.php`
- Create: `packages/workflow/database/migrations/2026_02_28_000004_create_workflow_runs_table.php`
- Create: `packages/workflow/database/migrations/2026_02_28_000005_create_workflow_run_steps_table.php`
- Modify: `packages/workflow/src/WorkflowServiceProvider.php` (add migrations)

**Step 1: Create all migration files**

Follow the project pattern: ULIDs, no `down()` method, `cascadeOnDelete()` for FKs. Use the table prefix from config.

```php
// 2026_02_28_000001_create_workflows_table.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('workflow.table_prefix', '');

        Schema::create($prefix . 'workflows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignUlid('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type');
            $table->json('trigger_config')->nullable();
            $table->json('canvas_data')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active', 'trigger_type']);
        });
    }
};
```

```php
// 2026_02_28_000002_create_workflow_nodes_table.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('workflow.table_prefix', '');

        Schema::create($prefix . 'workflow_nodes', function (Blueprint $table) use ($prefix) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workflow_id')->constrained($prefix . 'workflows')->cascadeOnDelete();
            $table->string('node_id');
            $table->string('type');
            $table->string('action_type')->nullable();
            $table->json('config')->nullable();
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->timestamps();

            $table->unique(['workflow_id', 'node_id']);
        });
    }
};
```

```php
// 2026_02_28_000003_create_workflow_edges_table.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('workflow.table_prefix', '');

        Schema::create($prefix . 'workflow_edges', function (Blueprint $table) use ($prefix) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workflow_id')->constrained($prefix . 'workflows')->cascadeOnDelete();
            $table->string('edge_id');
            $table->foreignUlid('source_node_id')->constrained($prefix . 'workflow_nodes')->cascadeOnDelete();
            $table->foreignUlid('target_node_id')->constrained($prefix . 'workflow_nodes')->cascadeOnDelete();
            $table->string('condition_label')->nullable();
            $table->json('condition_config')->nullable();
            $table->timestamps();

            $table->unique(['workflow_id', 'edge_id']);
        });
    }
};
```

```php
// 2026_02_28_000004_create_workflow_runs_table.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('workflow.table_prefix', '');

        Schema::create($prefix . 'workflow_runs', function (Blueprint $table) use ($prefix) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workflow_id')->constrained($prefix . 'workflows')->cascadeOnDelete();
            $table->string('trigger_record_type')->nullable();
            $table->string('trigger_record_id')->nullable();
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('context_data')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'status']);
            $table->index(['trigger_record_type', 'trigger_record_id']);
        });
    }
};
```

```php
// 2026_02_28_000005_create_workflow_run_steps_table.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('workflow.table_prefix', '');

        Schema::create($prefix . 'workflow_run_steps', function (Blueprint $table) use ($prefix) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workflow_run_id')->constrained($prefix . 'workflow_runs')->cascadeOnDelete();
            $table->foreignUlid('workflow_node_id')->constrained($prefix . 'workflow_nodes')->cascadeOnDelete();
            $table->string('status');
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workflow_run_id', 'status']);
        });
    }
};
```

**Step 2: Update ServiceProvider to load migrations**

Add `->hasMigrations([...])` or `->runsMigrations()` to `configurePackage()`.

**Step 3: Commit**

```bash
git add packages/workflow/database packages/workflow/src/WorkflowServiceProvider.php
git commit -m "feat(workflow): add database migrations for all workflow tables"
```

---

### Task 4: Eloquent Models

**Files:**
- Create: `packages/workflow/src/Models/Workflow.php`
- Create: `packages/workflow/src/Models/WorkflowNode.php`
- Create: `packages/workflow/src/Models/WorkflowEdge.php`
- Create: `packages/workflow/src/Models/WorkflowRun.php`
- Create: `packages/workflow/src/Models/WorkflowRunStep.php`
- Create: `packages/workflow/tests/Feature/ModelsTest.php`

**Step 1: Write the failing test**

```php
// packages/workflow/tests/Feature/ModelsTest.php
<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\StepStatus;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Models\WorkflowEdge;
use Relaticle\Workflow\Models\WorkflowNode;
use Relaticle\Workflow\Models\WorkflowRun;
use Relaticle\Workflow\Models\WorkflowRunStep;

it('creates a workflow with nodes and edges', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => ['model' => 'App\\Models\\Company', 'event' => 'created'],
    ]);

    $triggerNode = $workflow->nodes()->create([
        'node_id' => 'node-1',
        'type' => NodeType::Trigger,
        'config' => ['event' => 'created'],
        'position_x' => 100,
        'position_y' => 200,
    ]);

    $actionNode = $workflow->nodes()->create([
        'node_id' => 'node-2',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
        'config' => ['to' => '{{record.email}}'],
        'position_x' => 100,
        'position_y' => 400,
    ]);

    $edge = $workflow->edges()->create([
        'edge_id' => 'edge-1',
        'source_node_id' => $triggerNode->id,
        'target_node_id' => $actionNode->id,
    ]);

    expect($workflow->nodes)->toHaveCount(2);
    expect($workflow->edges)->toHaveCount(1);
    expect($edge->sourceNode->id)->toBe($triggerNode->id);
    expect($edge->targetNode->id)->toBe($actionNode->id);
});

it('creates a workflow run with steps', function () {
    $workflow = Workflow::create([
        'name' => 'Run Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $node = $workflow->nodes()->create([
        'node_id' => 'node-1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
    ]);

    $run = $workflow->runs()->create([
        'status' => WorkflowRunStatus::Running,
        'started_at' => now(),
        'context_data' => ['record' => ['name' => 'Acme']],
    ]);

    $step = $run->steps()->create([
        'workflow_node_id' => $node->id,
        'status' => StepStatus::Completed,
        'input_data' => ['to' => 'test@example.com'],
        'output_data' => ['sent' => true],
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    expect($run->steps)->toHaveCount(1);
    expect($step->node->id)->toBe($node->id);
    expect($step->run->id)->toBe($run->id);
    expect($run->workflow->id)->toBe($workflow->id);
});

it('soft deletes a workflow', function () {
    $workflow = Workflow::create([
        'name' => 'Deletable',
        'trigger_type' => TriggerType::Manual,
    ]);

    $workflow->delete();

    expect(Workflow::count())->toBe(0);
    expect(Workflow::withTrashed()->count())->toBe(1);
});

it('casts trigger_type to TriggerType enum', function () {
    $workflow = Workflow::create([
        'name' => 'Cast Test',
        'trigger_type' => TriggerType::Webhook,
    ]);

    $workflow->refresh();

    expect($workflow->trigger_type)->toBe(TriggerType::Webhook);
});

it('casts config and canvas_data as arrays', function () {
    $workflow = Workflow::create([
        'name' => 'JSON Test',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => ['model' => 'App\\Models\\Task'],
        'canvas_data' => ['nodes' => [], 'edges' => []],
    ]);

    $workflow->refresh();

    expect($workflow->trigger_config)->toBeArray();
    expect($workflow->trigger_config['model'])->toBe('App\\Models\\Task');
    expect($workflow->canvas_data)->toBeArray();
});
```

**Step 2: Run test to verify it fails**

Run: `cd packages/workflow && vendor/bin/pest tests/Feature/ModelsTest.php`
Expected: FAIL — Model classes don't exist yet

**Step 3: Implement the models**

Create all 5 model files with relationships, casts, fillable, table prefix support. Follow project conventions: `HasUlids`, `SoftDeletes` (on Workflow), typed properties, casts array.

Key patterns per model:
- `Workflow`: hasMany nodes, edges, runs. Casts trigger_type to enum, trigger_config/canvas_data to array. SoftDeletes.
- `WorkflowNode`: belongsTo workflow. Casts type to NodeType enum, config to array.
- `WorkflowEdge`: belongsTo workflow, sourceNode, targetNode.
- `WorkflowRun`: belongsTo workflow. Casts status to enum, context_data to array.
- `WorkflowRunStep`: belongsTo run (as `run()`), node (as `node()`). Casts status to enum, input/output to array.

All models should override `getTable()` to respect `config('workflow.table_prefix')`.

**Step 4: Run tests to verify they pass**

Run: `cd packages/workflow && vendor/bin/pest tests/Feature/ModelsTest.php`
Expected: All 5 tests PASS

**Step 5: Commit**

```bash
git add packages/workflow/src/Models packages/workflow/tests/Feature/ModelsTest.php
git commit -m "feat(workflow): add Eloquent models with relationships and casts"
```

---

### Task 5: Facade & WorkflowManager Registration API

**Files:**
- Create: `packages/workflow/src/Facades/Workflow.php`
- Modify: `packages/workflow/src/WorkflowManager.php`
- Create: `packages/workflow/src/Actions/Contracts/WorkflowAction.php`
- Create: `packages/workflow/tests/Feature/WorkflowRegistrationTest.php`

**Step 1: Write the failing test**

```php
// packages/workflow/tests/Feature/WorkflowRegistrationTest.php
<?php

declare(strict_types=1);

use Relaticle\Workflow\Actions\Contracts\WorkflowAction;
use Relaticle\Workflow\Facades\Workflow;

it('registers a triggerable model with events and fields', function () {
    Workflow::registerTriggerableModel('App\\Models\\Company', [
        'label' => 'Company',
        'events' => ['created', 'updated', 'deleted'],
        'fields' => fn () => [
            'name' => ['type' => 'string', 'label' => 'Name'],
        ],
    ]);

    $models = Workflow::getTriggerableModels();

    expect($models)->toHaveKey('App\\Models\\Company');
    expect($models['App\\Models\\Company']['label'])->toBe('Company');
    expect($models['App\\Models\\Company']['events'])->toContain('created');
});

it('registers a custom action class', function () {
    $action = new class implements WorkflowAction {
        public function execute(array $config, array $context): array
        {
            return ['done' => true];
        }

        public static function label(): string
        {
            return 'Test Action';
        }

        public static function configSchema(): array
        {
            return [];
        }
    };

    Workflow::registerAction('test_action', $action::class);

    $actions = Workflow::getRegisteredActions();

    expect($actions)->toHaveKey('test_action');
});

it('rejects action classes not implementing WorkflowAction', function () {
    Workflow::registerAction('bad_action', \stdClass::class);
})->throws(\InvalidArgumentException::class);

it('configures tenancy scoping', function () {
    Workflow::useTenancy(
        scopeColumn: 'team_id',
        resolver: fn () => 'team-123',
    );

    $config = Workflow::getTenancyConfig();

    expect($config['scopeColumn'])->toBe('team_id');
    expect(($config['resolver'])())->toBe('team-123');
});

it('lists all registered models and actions', function () {
    Workflow::registerTriggerableModel('App\\Models\\Task', [
        'label' => 'Task',
        'events' => ['created'],
        'fields' => fn () => [],
    ]);

    Workflow::registerAction('send_webhook', get_class(new class implements WorkflowAction {
        public function execute(array $config, array $context): array { return []; }
        public static function label(): string { return 'Send Webhook'; }
        public static function configSchema(): array { return []; }
    }));

    expect(Workflow::getTriggerableModels())->not->toBeEmpty();
    expect(Workflow::getRegisteredActions())->not->toBeEmpty();
});
```

**Step 2: Run test to verify it fails**

Run: `cd packages/workflow && vendor/bin/pest tests/Feature/WorkflowRegistrationTest.php`
Expected: FAIL

**Step 3: Implement WorkflowAction interface, WorkflowManager, and Facade**

```php
// packages/workflow/src/Actions/Contracts/WorkflowAction.php
<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions\Contracts;

interface WorkflowAction
{
    public function execute(array $config, array $context): array;

    public static function label(): string;

    public static function configSchema(): array;
}
```

Implement `WorkflowManager` with:
- `registerTriggerableModel(string $modelClass, array $config): void`
- `getTriggerableModels(): array`
- `registerAction(string $key, string $actionClass): void` (validates implements WorkflowAction)
- `getRegisteredActions(): array`
- `useTenancy(string $scopeColumn, Closure $resolver): void`
- `getTenancyConfig(): ?array`

Create Facade extending `Illuminate\Support\Facades\Facade` with accessor `WorkflowManager::class`.

**Step 4: Run tests to verify they pass**

Run: `cd packages/workflow && vendor/bin/pest tests/Feature/WorkflowRegistrationTest.php`
Expected: All 5 tests PASS

**Step 5: Commit**

```bash
git add packages/workflow/src/Facades packages/workflow/src/WorkflowManager.php packages/workflow/src/Actions/Contracts packages/workflow/tests/Feature/WorkflowRegistrationTest.php
git commit -m "feat(workflow): add WorkflowManager registration API with facade"
```

---

## Phase 2: Engine Core

### Task 6: Variable Resolver

**Files:**
- Create: `packages/workflow/src/Engine/VariableResolver.php`
- Create: `packages/workflow/tests/Feature/VariableResolutionTest.php`

**Step 1: Write the failing test**

```php
// packages/workflow/tests/Feature/VariableResolutionTest.php
<?php

declare(strict_types=1);

use Relaticle\Workflow\Engine\VariableResolver;

it('resolves {{record.field_name}} from context', function () {
    $resolver = new VariableResolver();
    $context = ['record' => ['name' => 'Acme Corp', 'email' => 'hello@acme.com']];

    expect($resolver->resolve('Hello {{record.name}}', $context))->toBe('Hello Acme Corp');
    expect($resolver->resolve('{{record.email}}', $context))->toBe('hello@acme.com');
});

it('resolves nested variables {{record.company.name}}', function () {
    $resolver = new VariableResolver();
    $context = ['record' => ['company' => ['name' => 'Acme']]];

    expect($resolver->resolve('{{record.company.name}}', $context))->toBe('Acme');
});

it('resolves {{now}} and {{today}} date variables', function () {
    $resolver = new VariableResolver();

    $result = $resolver->resolve('Date: {{today}}', []);

    expect($result)->toContain('Date: ' . now()->toDateString());
});

it('resolves {{trigger.user.name}}', function () {
    $resolver = new VariableResolver();
    $context = ['trigger' => ['user' => ['name' => 'John']]];

    expect($resolver->resolve('By {{trigger.user.name}}', $context))->toBe('By John');
});

it('returns empty string for missing variables', function () {
    $resolver = new VariableResolver();

    expect($resolver->resolve('Hello {{record.missing}}', []))->toBe('Hello ');
});

it('resolves variables in arrays recursively', function () {
    $resolver = new VariableResolver();
    $context = ['record' => ['name' => 'Acme']];

    $config = [
        'subject' => 'Welcome {{record.name}}',
        'body' => 'Hi {{record.name}}, welcome!',
        'nested' => ['value' => '{{record.name}}'],
    ];

    $resolved = $resolver->resolveArray($config, $context);

    expect($resolved['subject'])->toBe('Welcome Acme');
    expect($resolved['body'])->toBe('Hi Acme, welcome!');
    expect($resolved['nested']['value'])->toBe('Acme');
});

it('leaves non-variable strings untouched', function () {
    $resolver = new VariableResolver();

    expect($resolver->resolve('No variables here', []))->toBe('No variables here');
});
```

**Step 2: Run to verify failure, implement VariableResolver, run to verify pass, commit**

Run: `cd packages/workflow && vendor/bin/pest tests/Feature/VariableResolutionTest.php`

```bash
git commit -m "feat(workflow): add VariableResolver for template interpolation"
```

---

### Task 7: Condition Evaluator

**Files:**
- Create: `packages/workflow/src/Engine/ConditionEvaluator.php`
- Create: `packages/workflow/tests/Feature/ConditionEvaluatorTest.php`

**Step 1: Write the failing test**

```php
// packages/workflow/tests/Feature/ConditionEvaluatorTest.php
<?php

declare(strict_types=1);

use Relaticle\Workflow\Engine\ConditionEvaluator;

it('evaluates "equals" condition', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['status' => 'active']];

    expect($evaluator->evaluate([
        'field' => 'record.status',
        'operator' => 'equals',
        'value' => 'active',
    ], $context))->toBeTrue();

    expect($evaluator->evaluate([
        'field' => 'record.status',
        'operator' => 'equals',
        'value' => 'inactive',
    ], $context))->toBeFalse();
});

it('evaluates "not_equals" condition', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['status' => 'active']];

    expect($evaluator->evaluate([
        'field' => 'record.status',
        'operator' => 'not_equals',
        'value' => 'inactive',
    ], $context))->toBeTrue();
});

it('evaluates "contains" condition', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['email' => 'john@acme.com']];

    expect($evaluator->evaluate([
        'field' => 'record.email',
        'operator' => 'contains',
        'value' => 'acme',
    ], $context))->toBeTrue();
});

it('evaluates "greater_than" and "less_than" conditions', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['amount' => 5000]];

    expect($evaluator->evaluate([
        'field' => 'record.amount',
        'operator' => 'greater_than',
        'value' => 1000,
    ], $context))->toBeTrue();

    expect($evaluator->evaluate([
        'field' => 'record.amount',
        'operator' => 'less_than',
        'value' => 10000,
    ], $context))->toBeTrue();
});

it('evaluates "is_empty" and "is_not_empty" conditions', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['name' => 'Acme', 'notes' => '']];

    expect($evaluator->evaluate([
        'field' => 'record.notes',
        'operator' => 'is_empty',
    ], $context))->toBeTrue();

    expect($evaluator->evaluate([
        'field' => 'record.name',
        'operator' => 'is_not_empty',
    ], $context))->toBeTrue();
});

it('evaluates "in" condition', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['status' => 'active']];

    expect($evaluator->evaluate([
        'field' => 'record.status',
        'operator' => 'in',
        'value' => ['active', 'pending'],
    ], $context))->toBeTrue();
});

it('evaluates compound AND conditions', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['status' => 'active', 'amount' => 5000]];

    expect($evaluator->evaluateGroup([
        'operator' => 'and',
        'conditions' => [
            ['field' => 'record.status', 'operator' => 'equals', 'value' => 'active'],
            ['field' => 'record.amount', 'operator' => 'greater_than', 'value' => 1000],
        ],
    ], $context))->toBeTrue();

    expect($evaluator->evaluateGroup([
        'operator' => 'and',
        'conditions' => [
            ['field' => 'record.status', 'operator' => 'equals', 'value' => 'active'],
            ['field' => 'record.amount', 'operator' => 'greater_than', 'value' => 9999],
        ],
    ], $context))->toBeFalse();
});

it('evaluates compound OR conditions', function () {
    $evaluator = new ConditionEvaluator();
    $context = ['record' => ['status' => 'inactive', 'amount' => 5000]];

    expect($evaluator->evaluateGroup([
        'operator' => 'or',
        'conditions' => [
            ['field' => 'record.status', 'operator' => 'equals', 'value' => 'active'],
            ['field' => 'record.amount', 'operator' => 'greater_than', 'value' => 1000],
        ],
    ], $context))->toBeTrue();
});
```

**Step 2: Run to verify failure, implement ConditionEvaluator, run to verify pass, commit**

```bash
git commit -m "feat(workflow): add ConditionEvaluator with operators and compound logic"
```

---

### Task 8: Graph Walker

**Files:**
- Create: `packages/workflow/src/Engine/GraphWalker.php`
- Create: `packages/workflow/tests/Feature/GraphWalkerTest.php`

**Step 1: Write the failing test**

Test that GraphWalker can:
- Find the trigger node (entry point) in a workflow
- Return outgoing edges from a node
- Return the next nodes from a given node
- Handle condition branching (return edges labeled "yes"/"no")
- Detect when a node has no outgoing edges (terminal)

**Step 2: Implement GraphWalker**

GraphWalker operates on in-memory collections of WorkflowNode and WorkflowEdge. It does not dispatch jobs — it only navigates the graph.

Methods:
- `__construct(Collection $nodes, Collection $edges)`
- `findTriggerNode(): ?WorkflowNode`
- `getOutgoingEdges(WorkflowNode $node): Collection`
- `getNextNodes(WorkflowNode $node): Collection`
- `getEdgeByLabel(WorkflowNode $node, string $label): ?WorkflowEdge`
- `isTerminal(WorkflowNode $node): bool`

**Step 3: Run tests, commit**

```bash
git commit -m "feat(workflow): add GraphWalker for traversing workflow node graph"
```

---

### Task 9: Workflow Executor & Jobs

**Files:**
- Create: `packages/workflow/src/Engine/WorkflowExecutor.php`
- Create: `packages/workflow/src/Actions/BaseAction.php`
- Create: `packages/workflow/src/Jobs/ExecuteWorkflowJob.php`
- Create: `packages/workflow/src/Jobs/ExecuteStepJob.php`
- Create: `packages/workflow/src/Events/WorkflowTriggered.php`
- Create: `packages/workflow/src/Events/WorkflowRunCompleted.php`
- Create: `packages/workflow/src/Events/WorkflowRunFailed.php`
- Create: `packages/workflow/tests/Feature/WorkflowExecutionTest.php`

**Step 1: Write the failing test**

```php
// packages/workflow/tests/Feature/WorkflowExecutionTest.php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Relaticle\Workflow\Actions\Contracts\WorkflowAction;
use Relaticle\Workflow\Engine\WorkflowExecutor;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\StepStatus;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Facades\Workflow;
use Relaticle\Workflow\Models\Workflow as WorkflowModel;

beforeEach(function () {
    // Register a test action
    Workflow::registerAction('log_message', get_class(new class implements WorkflowAction {
        public function execute(array $config, array $context): array
        {
            return ['logged' => $config['message'] ?? 'no message'];
        }
        public static function label(): string { return 'Log Message'; }
        public static function configSchema(): array { return []; }
    }));
});

it('executes a linear workflow (trigger → action → action)', function () {
    $workflow = createLinearWorkflow();

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, ['record' => ['name' => 'Acme']]);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);
    expect($run->steps)->toHaveCount(2); // 2 action nodes (trigger node is not a "step")
    expect($run->steps->every(fn ($step) => $step->status === StepStatus::Completed))->toBeTrue();
});

it('executes branching workflow and follows "yes" path', function () {
    $workflow = createBranchingWorkflow();

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, [
        'record' => ['amount' => 5000],
    ]);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);

    $completedSteps = $run->steps->where('status', StepStatus::Completed);
    $skippedSteps = $run->steps->where('status', StepStatus::Skipped);

    expect($completedSteps)->not->toBeEmpty();
    expect($skippedSteps)->not->toBeEmpty();
});

it('marks run as failed when an action throws exception', function () {
    Workflow::registerAction('failing_action', get_class(new class implements WorkflowAction {
        public function execute(array $config, array $context): array
        {
            throw new \RuntimeException('Something broke');
        }
        public static function label(): string { return 'Failing'; }
        public static function configSchema(): array { return []; }
    }));

    $workflow = WorkflowModel::create([
        'name' => 'Failing Workflow',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger',
        'type' => NodeType::Trigger,
    ]);

    $failNode = $workflow->nodes()->create([
        'node_id' => 'fail',
        'type' => NodeType::Action,
        'action_type' => 'failing_action',
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $failNode->id,
    ]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, []);

    expect($run->status)->toBe(WorkflowRunStatus::Failed);
    expect($run->error_message)->toContain('Something broke');
});

it('stops execution at a stop node', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Stop Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 't', 'type' => NodeType::Trigger]);
    $action = $workflow->nodes()->create(['node_id' => 'a', 'type' => NodeType::Action, 'action_type' => 'log_message', 'config' => ['message' => 'hi']]);
    $stop = $workflow->nodes()->create(['node_id' => 's', 'type' => NodeType::Stop]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $action->id]);
    $workflow->edges()->create(['edge_id' => 'e2', 'source_node_id' => $action->id, 'target_node_id' => $stop->id]);

    $executor = app(WorkflowExecutor::class);
    $run = $executor->execute($workflow, []);

    expect($run->status)->toBe(WorkflowRunStatus::Completed);
});

// Helper functions at bottom of file
function createLinearWorkflow(): WorkflowModel
{
    $workflow = WorkflowModel::create([
        'name' => 'Linear Workflow',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);
    $action1 = $workflow->nodes()->create(['node_id' => 'action1', 'type' => NodeType::Action, 'action_type' => 'log_message', 'config' => ['message' => 'step 1']]);
    $action2 = $workflow->nodes()->create(['node_id' => 'action2', 'type' => NodeType::Action, 'action_type' => 'log_message', 'config' => ['message' => 'step 2']]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $action1->id]);
    $workflow->edges()->create(['edge_id' => 'e2', 'source_node_id' => $action1->id, 'target_node_id' => $action2->id]);

    return $workflow;
}

function createBranchingWorkflow(): WorkflowModel
{
    $workflow = WorkflowModel::create([
        'name' => 'Branching Workflow',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);
    $condition = $workflow->nodes()->create([
        'node_id' => 'condition',
        'type' => NodeType::Condition,
        'config' => ['field' => 'record.amount', 'operator' => 'greater_than', 'value' => 1000],
    ]);
    $yesAction = $workflow->nodes()->create(['node_id' => 'yes_action', 'type' => NodeType::Action, 'action_type' => 'log_message', 'config' => ['message' => 'big deal']]);
    $noAction = $workflow->nodes()->create(['node_id' => 'no_action', 'type' => NodeType::Action, 'action_type' => 'log_message', 'config' => ['message' => 'small deal']]);

    $workflow->edges()->create(['edge_id' => 'e1', 'source_node_id' => $trigger->id, 'target_node_id' => $condition->id]);
    $workflow->edges()->create(['edge_id' => 'e2', 'source_node_id' => $condition->id, 'target_node_id' => $yesAction->id, 'condition_label' => 'yes']);
    $workflow->edges()->create(['edge_id' => 'e3', 'source_node_id' => $condition->id, 'target_node_id' => $noAction->id, 'condition_label' => 'no']);

    return $workflow;
}
```

**Step 2: Implement WorkflowExecutor, BaseAction, ExecuteWorkflowJob, ExecuteStepJob, Events**

The `WorkflowExecutor` is the heart of the engine. For synchronous test execution it walks the graph directly. For async production use, `ExecuteWorkflowJob` dispatches `ExecuteStepJob` per node.

Key design: `WorkflowExecutor::execute()` runs synchronously (for testing and manual triggers). `ExecuteWorkflowJob::handle()` calls `WorkflowExecutor::execute()` within a queued job context.

**Step 3: Run tests, commit**

```bash
git commit -m "feat(workflow): add WorkflowExecutor with graph walking, branching, and audit trail"
```

---

### Task 10: Audit Trail Tests

**Files:**
- Create: `packages/workflow/tests/Feature/WorkflowAuditTrailTest.php`

**Step 1: Write the failing test**

Test that every execution creates proper WorkflowRun and WorkflowRunStep records with:
- Correct status transitions (pending → running → completed/failed)
- input_data and output_data populated on each step
- started_at and completed_at timestamps on each step
- Failed steps have error_message
- Skipped steps (wrong branch) have StepStatus::Skipped
- Run-level error_message on failure

**Step 2: These tests should pass if Task 9 was implemented correctly. If not, fix the executor.**

**Step 3: Commit**

```bash
git commit -m "test(workflow): add comprehensive audit trail tests"
```

---

## Phase 3: Triggers

### Task 11: Record Event Trigger

**Files:**
- Create: `packages/workflow/src/Triggers/RecordEventTrigger.php`
- Create: `packages/workflow/src/Observers/WorkflowModelObserver.php`
- Create: `packages/workflow/tests/Feature/RecordEventTriggerTest.php`
- Create: `packages/workflow/tests/database/migrations/create_test_models_table.php`
- Create: `packages/workflow/tests/Fixtures/TestCompany.php`

**Step 1: Create test fixtures**

Create a test model (`TestCompany`) with a migration so tests have a real Eloquent model to trigger against. Place in `tests/Fixtures/`.

**Step 2: Write the failing test**

Test that:
- Creating a TestCompany dispatches ExecuteWorkflowJob for matching active workflows
- Updating a specific field triggers field-change workflows
- Unrelated field changes don't trigger
- Inactive workflows are skipped
- Tenant-scoped workflows don't cross-fire
- The record is passed as context data

**Step 3: Implement RecordEventTrigger and WorkflowModelObserver**

The observer is dynamically attached to models registered via `Workflow::registerTriggerableModel()`. On `created`/`updated`/`deleted`, it queries active workflows matching the trigger_config and dispatches `ExecuteWorkflowJob`.

**Step 4: Run tests, commit**

```bash
git commit -m "feat(workflow): add RecordEventTrigger with dynamic model observer"
```

---

### Task 12: Scheduled Trigger

**Files:**
- Create: `packages/workflow/src/Triggers/ScheduledTrigger.php`
- Create: `packages/workflow/src/Jobs/EvaluateScheduledWorkflowsJob.php`
- Create: `packages/workflow/tests/Feature/ScheduledTriggerTest.php`

**Step 1: Write failing tests for cron, date-field, and inactivity triggers**

**Step 2: Implement ScheduledTrigger and EvaluateScheduledWorkflowsJob**

The job runs via Laravel scheduler (every minute). It queries active workflows with `trigger_type = time_based`, evaluates their cron/date-field/inactivity config, and dispatches `ExecuteWorkflowJob` for matches.

**Step 3: Run tests, commit**

```bash
git commit -m "feat(workflow): add ScheduledTrigger with cron, date-field, and inactivity support"
```

---

### Task 13: Manual Trigger

**Files:**
- Create: `packages/workflow/src/Triggers/ManualTrigger.php`
- Create: `packages/workflow/src/Http/Controllers/WorkflowApiController.php`
- Create: `packages/workflow/tests/Feature/ManualTriggerTest.php`
- Create: `packages/workflow/routes/api.php`

**Step 1: Write failing tests for manual trigger via API endpoint**

**Step 2: Implement ManualTrigger, API controller, and route**

POST `/workflow/api/workflows/{workflow}/trigger` with optional `record_type` and `record_id`.

**Step 3: Run tests, commit**

```bash
git commit -m "feat(workflow): add ManualTrigger with API endpoint"
```

---

### Task 14: Webhook Trigger

**Files:**
- Create: `packages/workflow/src/Triggers/WebhookTrigger.php`
- Create: `packages/workflow/src/Http/Controllers/WebhookTriggerController.php`
- Create: `packages/workflow/tests/Feature/WebhookTriggerTest.php`

**Step 1: Write failing tests for webhook POST trigger**

**Step 2: Implement WebhookTrigger and controller**

POST `/workflow/api/webhooks/{workflow}` — accepts any JSON payload, passes it as context.

**Step 3: Run tests, commit**

```bash
git commit -m "feat(workflow): add WebhookTrigger with incoming POST endpoint"
```

---

## Phase 4: Built-in Actions

### Task 15: Delay Action

**Files:**
- Create: `packages/workflow/src/Actions/DelayAction.php`
- Create: `packages/workflow/tests/Feature/DelayActionTest.php`

**Step 1: Write failing test**

Test that delay nodes re-dispatch the next step with `->delay()`.

**Step 2: Implement DelayAction — uses `Bus::dispatch(ExecuteStepJob::make(...))->delay(...))`**

**Step 3: Run tests, commit**

```bash
git commit -m "feat(workflow): add DelayAction with configurable wait duration"
```

---

### Task 16: Loop Action

**Files:**
- Create: `packages/workflow/src/Actions/LoopAction.php`
- Create: `packages/workflow/tests/Feature/LoopActionTest.php`

**Step 1: Write failing tests for loop iteration, loop.item/loop.index variables, empty collection**

**Step 2: Implement LoopAction — iterates a collection, executes sub-path for each item**

**Step 3: Run tests, commit**

```bash
git commit -m "feat(workflow): add LoopAction with collection iteration and loop variables"
```

---

### Task 17: Send Webhook & Send Email Actions

**Files:**
- Create: `packages/workflow/src/Actions/SendWebhookAction.php`
- Create: `packages/workflow/src/Actions/SendEmailAction.php`
- Create: `packages/workflow/src/Actions/HttpRequestAction.php`
- Create: `packages/workflow/tests/Feature/BuiltInActionsTest.php`

**Step 1: Write failing tests (use Http::fake() and Mail::fake())**

**Step 2: Implement all three actions**

**Step 3: Run tests, commit**

```bash
git commit -m "feat(workflow): add SendWebhook, SendEmail, and HttpRequest built-in actions"
```

---

## Phase 5: Tenancy & Scoping

### Task 18: Tenancy Scoping

**Files:**
- Create: `packages/workflow/src/Http/Middleware/WorkflowTenancyMiddleware.php`
- Create: `packages/workflow/tests/Feature/TenancyScopingTest.php`

**Step 1: Write failing tests**

Test that workflows are scoped to the configured tenant column, cross-tenant access is prevented, and tenancy works when not configured.

**Step 2: Implement middleware and model scoping**

Apply tenant scoping via global scope on Workflow model when tenancy is configured.

**Step 3: Run tests, commit**

```bash
git commit -m "feat(workflow): add configurable tenancy scoping with middleware"
```

---

## Phase 6: Canvas API

### Task 19: Canvas Save/Load API

**Files:**
- Create: `packages/workflow/src/Http/Controllers/CanvasController.php`
- Create: `packages/workflow/tests/Feature/CanvasApiTest.php`
- Modify: `packages/workflow/routes/api.php`

**Step 1: Write failing tests**

Test:
- PUT `/workflow/api/workflows/{id}/canvas` saves canvas_data JSON and syncs nodes/edges to DB
- GET `/workflow/api/workflows/{id}/canvas` returns canvas_data + registered models/actions for sidebar
- Validation: nodes must have type, action nodes must have action_type
- Tenant scoping on API

**Step 2: Implement CanvasController**

The save endpoint: receives X6 graph JSON, stores it in `canvas_data`, and upserts/deletes `workflow_nodes` and `workflow_edges` to keep the normalized DB in sync.

**Step 3: Run tests, commit**

```bash
git commit -m "feat(workflow): add Canvas API for save/load workflow graph data"
```

---

## Phase 7: Filament Adapter

### Task 20: Filament Plugin & Workflow Resource

**Files:**
- Create: `packages/workflow/src/Filament/WorkflowPlugin.php`
- Create: `packages/workflow/src/Filament/Resources/WorkflowResource.php`
- Create: `packages/workflow/src/Filament/Resources/WorkflowResource/Pages/ListWorkflows.php`
- Create: `packages/workflow/src/Filament/Resources/WorkflowResource/Pages/CreateWorkflow.php`
- Create: `packages/workflow/src/Filament/Resources/WorkflowResource/Pages/EditWorkflow.php`
- Modify: `packages/workflow/src/WorkflowServiceProvider.php` (conditional Filament loading)
- Create: `packages/workflow/tests/Feature/WorkflowResourceTest.php`

**Step 1: Write failing tests**

Test Filament resource CRUD using Filament's `Livewire::test()` helpers: list, create, edit, toggle active, soft delete.

**Step 2: Implement WorkflowPlugin (implements `Filament\Contracts\Plugin`), WorkflowResource with form/table schemas, and CRUD pages**

Follow the pattern from custom-fields: `WorkflowPlugin::make()` registered in panel provider.

**Step 3: Update ServiceProvider to conditionally load Filament classes**

```php
public function packageBooted(): void
{
    if (class_exists(\Filament\Panel::class)) {
        // Register Filament assets, views, etc.
    }
}
```

**Step 4: Run tests, commit**

```bash
git commit -m "feat(workflow): add Filament plugin with WorkflowResource CRUD"
```

---

### Task 21: Workflow Builder Page (X6 Canvas)

**Files:**
- Create: `packages/workflow/src/Filament/Resources/WorkflowResource/Pages/WorkflowBuilder.php`
- Create: `packages/workflow/resources/views/builder.blade.php`
- Create: `packages/workflow/resources/js/workflow-builder/index.js`
- Create: `packages/workflow/resources/js/workflow-builder/graph.js`
- Create: `packages/workflow/resources/js/workflow-builder/nodes/TriggerNode.js`
- Create: `packages/workflow/resources/js/workflow-builder/nodes/ActionNode.js`
- Create: `packages/workflow/resources/js/workflow-builder/nodes/ConditionNode.js`
- Create: `packages/workflow/resources/js/workflow-builder/nodes/DelayNode.js`
- Create: `packages/workflow/resources/js/workflow-builder/nodes/LoopNode.js`
- Create: `packages/workflow/resources/js/workflow-builder/nodes/StopNode.js`
- Create: `packages/workflow/resources/js/workflow-builder/sidebar.js`
- Create: `packages/workflow/resources/js/workflow-builder/toolbar.js`
- Create: `packages/workflow/resources/js/workflow-builder/config-panel.js`
- Create: `packages/workflow/resources/css/workflow-builder.css`
- Create: `packages/workflow/vite.config.js`
- Create: `packages/workflow/package.json`

**Step 1: Set up Vite build for X6 assets**

```json
// packages/workflow/package.json
{
    "private": true,
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "dependencies": {
        "@antv/x6": "^2.18",
        "@antv/x6-plugin-selection": "^2.2",
        "@antv/x6-plugin-minimap": "^2.1",
        "@antv/x6-plugin-snapline": "^2.1",
        "@antv/x6-plugin-keyboard": "^2.2",
        "@antv/x6-plugin-clipboard": "^2.2",
        "@antv/x6-plugin-history": "^2.3",
        "@antv/x6-plugin-dnd": "^2.1",
        "@antv/x6-plugin-export": "^2.1",
        "@antv/x6-plugin-stencil": "^2.1"
    },
    "devDependencies": {
        "vite": "^7.3"
    }
}
```

**Step 2: Implement the Blade template**

The builder page uses `wire:ignore` to prevent Livewire from interfering with the X6 canvas. Layout: sidebar (left) | canvas (center) | config panel (right).

**Step 3: Implement X6 graph setup (graph.js)**

Initialize X6 Graph with plugins: selection, minimap, snapline, keyboard, clipboard, history, dnd, stencil. Configure grid, zoom, pan.

**Step 4: Implement custom HTML nodes**

Each node type (TriggerNode, ActionNode, etc.) is registered as an X6 HTML shape with:
- Colored header bar (green/blue/yellow/gray/purple/red)
- Icon + title
- Summary text showing config
- Input/output ports

**Step 5: Implement sidebar, toolbar, config panel**

- Sidebar: Stencil plugin with draggable node prototypes grouped by category (Triggers, Actions, Logic)
- Toolbar: Buttons for undo/redo, zoom in/out, fit, save, toggle active
- Config panel: Opens when clicking a node. Shows form fields based on node type. Uses API to load available models/fields/actions.

**Step 6: Wire up save/load**

On "Save" button click: serialize X6 graph to JSON, POST to canvas API. On page load: fetch canvas data from API, deserialize into X6 graph.

**Step 7: Commit**

```bash
git add packages/workflow/resources packages/workflow/package.json packages/workflow/vite.config.js
git commit -m "feat(workflow): add X6-based visual workflow builder with drag-drop nodes"
```

---

## Phase 8: Browser Tests

### Task 22: Pest Browser Tests for Canvas

**Files:**
- Create: `packages/workflow/tests/Browser/WorkflowCanvasTest.php`
- Create: `packages/workflow/tests/Browser/NodeDragDropTest.php`
- Create: `packages/workflow/tests/Browser/NodeConnectionTest.php`
- Create: `packages/workflow/tests/Browser/NodeConfigPanelTest.php`
- Create: `packages/workflow/tests/Browser/WorkflowVisualRegressionTest.php`

**Prerequisite:** Install Playwright: `npm install playwright@latest && npx playwright install`

**Step 1: Write browser tests**

```php
// packages/workflow/tests/Browser/WorkflowCanvasTest.php
<?php

declare(strict_types=1);

it('renders the X6 canvas on the builder page', function () {
    $page = visit('/admin/workflows/create/builder');

    $page->assertVisible('[data-test="workflow-canvas"]');
    $page->assertVisible('[data-test="node-sidebar"]');
    $page->assertVisible('[data-test="toolbar"]');
    $page->assertNoJavaScriptErrors();
});

it('shows empty canvas for new workflow', function () {
    $page = visit('/admin/workflows/create/builder');

    $page->assertScript(
        'document.querySelectorAll("[data-test=\\"workflow-node\\"]").length',
        0
    );
});
```

```php
// packages/workflow/tests/Browser/NodeDragDropTest.php
<?php

declare(strict_types=1);

it('drags a trigger node from sidebar onto canvas', function () {
    $page = visit('/admin/workflows/create/builder');

    $page->drag('[data-test="sidebar-trigger-node"]', '[data-test="workflow-canvas"]');
    $page->assertPresent('[data-test="workflow-node"]');
    $page->assertScript(
        'document.querySelectorAll("[data-test=\\"workflow-node\\"]").length',
        1
    );
});
```

```php
// packages/workflow/tests/Browser/WorkflowVisualRegressionTest.php
<?php

declare(strict_types=1);

it('matches screenshot of empty canvas', function () {
    $page = visit('/admin/workflows/create/builder');

    $page->screenshotElement('[data-test="workflow-canvas"]');
    $page->assertScreenshotMatches();
});
```

**Step 2: These tests validate the frontend implementation from Task 21. Fix any issues.**

**Step 3: Commit**

```bash
git commit -m "test(workflow): add Pest browser tests for canvas, drag-drop, and visual regression"
```

---

## Phase 9: Integration with Relaticle

### Task 23: Register Relaticle Models & Actions

**Files:**
- Create: `app/Providers/WorkflowServiceProvider.php` (in Relaticle host app)
- Modify: `bootstrap/providers.php` (register the provider)

**Step 1: Create Relaticle-specific WorkflowServiceProvider**

Register Company, People, Opportunity, Task as triggerable models with their fields. Register CRM-specific actions: MoveStageAction, AssignUserAction, CreateTaskAction, AddNoteAction, SetCustomFieldAction.

**Step 2: Register WorkflowPlugin in AppPanelProvider**

```php
->plugins([
    WorkflowPlugin::make(),
])
```

**Step 3: Run Relaticle's test suite to ensure no regressions**

```bash
composer test
```

**Step 4: Commit**

```bash
git commit -m "feat: integrate relaticle/workflow package with CRM models and actions"
```

---

## Phase 10: Workflow Stats Widget

### Task 24: Dashboard Widget

**Files:**
- Create: `packages/workflow/src/Filament/Widgets/WorkflowStatsWidget.php`
- Create: `packages/workflow/tests/Feature/WorkflowStatsWidgetTest.php`

**Step 1: Write failing test for widget data (total runs, success rate, avg execution time)**

**Step 2: Implement Filament stats widget**

**Step 3: Run tests, commit**

```bash
git commit -m "feat(workflow): add WorkflowStatsWidget for dashboard metrics"
```

---

## Summary

| Phase | Tasks | Focus |
|---|---|---|
| 1 | 1-5 | Package scaffold, enums, migrations, models, facade |
| 2 | 6-10 | Engine core: variables, conditions, graph walker, executor, audit |
| 3 | 11-14 | Triggers: record events, scheduled, manual, webhook |
| 4 | 15-17 | Built-in actions: delay, loop, webhook, email |
| 5 | 18 | Tenancy scoping |
| 6 | 19 | Canvas API |
| 7 | 20-21 | Filament adapter + X6 visual builder |
| 8 | 22 | Browser tests |
| 9 | 23 | Relaticle integration |
| 10 | 24 | Dashboard widget |

**Total: 24 tasks, ~14 test files, ~60 feature tests, ~5 browser tests**
