# Relaticle Workflow

Visual workflow automation engine for Laravel with an optional Filament adapter.

Build automation workflows with a drag-and-drop canvas, configure triggers and actions through a clean UI, and let the engine execute them reliably in the background.

## Requirements

- PHP 8.3+
- Laravel 11 or 12
- Filament 5 (optional, for the admin UI)
- PostgreSQL recommended

## Installation

```bash
composer require relaticle/workflow
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag="workflow-migrations"
php artisan migrate
```

Publish the config (optional):

```bash
php artisan vendor:publish --tag="workflow-config"
```

## Filament Integration

Register the plugin in your Filament panel provider:

```php
use Relaticle\Workflow\Filament\WorkflowPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            WorkflowPlugin::make(),
        ]);
}
```

## Architecture

### Triggers

Triggers define what starts a workflow.

| Trigger | Description |
|---------|-------------|
| `RecordEventTrigger` | Fires on record created/updated/deleted |
| `ScheduledTrigger` | Runs on a cron schedule |
| `ManualTrigger` | Triggered manually by a user |
| `WebhookTrigger` | Fires when an external webhook hits the endpoint |

### Actions

Actions are the building blocks of your workflow.

**Records:** CreateRecord, UpdateRecord, DeleteRecord, FindRecord
**Communication:** SendEmail, BroadcastMessage, SendWebhook
**Logic:** Delay, Loop, Formula, Aggregate, ParseJson, RandomNumber, AdjustTime
**AI:** PromptCompletion, Summarize, Classify, Celebration

### Flow Control Nodes

- **Condition** — Branch based on field values (all/any match)
- **Filter** — Continue only if conditions are met (no else branch)
- **Switch** — Route to different branches by field value
- **Delay** — Pause execution for a duration
- **Loop** — Iterate over a collection
- **Stop** — End the workflow with a reason

### Engine

The execution engine (`WorkflowExecutor`) walks the workflow graph, resolves variables with `{{ }}` syntax, evaluates conditions, and executes actions. Runs are tracked with full step-by-step audit trails.

## Registering Custom Actions

```php
use Relaticle\Workflow\WorkflowManager;

app(WorkflowManager::class)->registerAction('my_action', MyCustomAction::class);
```

Your action should implement `Relaticle\Workflow\Actions\Contracts\WorkflowAction`.

## Registering Triggerable Models

```php
app(WorkflowManager::class)->registerTriggerableModel(Contact::class, [
    'label' => 'Contacts',
    'fields' => ['name', 'email', 'phone'],
]);
```

## Testing

```bash
composer test
```

## License

MIT
