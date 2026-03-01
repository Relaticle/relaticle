<?php

declare(strict_types=1);

namespace Relaticle\Workflow;

use Closure;
use InvalidArgumentException;
use Relaticle\Workflow\Actions\Contracts\WorkflowAction;
use Relaticle\Workflow\Actions\CreateRecordAction;
use Relaticle\Workflow\Actions\DeleteRecordAction;
use Relaticle\Workflow\Actions\DelayAction;
use Relaticle\Workflow\Actions\FindRecordAction;
use Relaticle\Workflow\Actions\HttpRequestAction;
use Relaticle\Workflow\Actions\LoopAction;
use Relaticle\Workflow\Actions\SendEmailAction;
use Relaticle\Workflow\Actions\SendWebhookAction;
use Relaticle\Workflow\Actions\UpdateRecordAction;
use Relaticle\Workflow\Observers\WorkflowModelObserver;
use Relaticle\Workflow\Schema\EntityDefinition;
use Relaticle\Workflow\Schema\RelaticleSchema;

class WorkflowManager
{
    /**
     * Registered triggerable models keyed by class name.
     *
     * @var array<string, array{label: string, events: list<string>, fields: Closure}>
     */
    protected array $triggerableModels = [];

    /**
     * Registered action classes keyed by action key.
     *
     * @var array<string, class-string<WorkflowAction>>
     */
    protected array $actions = [];

    /**
     * Tenancy configuration.
     *
     * @var array{scopeColumn: string, resolver: Closure}|null
     */
    protected ?array $tenancyConfig = null;

    /**
     * Built-in Relaticle action classes.
     *
     * @return array<string, class-string<WorkflowAction>>
     */
    public function getActions(): array
    {
        return array_merge([
            'send_email' => SendEmailAction::class,
            'send_webhook' => SendWebhookAction::class,
            'http_request' => HttpRequestAction::class,
            'delay' => DelayAction::class,
            'loop' => LoopAction::class,
            'create_record' => CreateRecordAction::class,
            'update_record' => UpdateRecordAction::class,
            'find_record' => FindRecordAction::class,
            'delete_record' => DeleteRecordAction::class,
        ], $this->actions);
    }

    /**
     * Get trigger entities from the RelaticleSchema.
     *
     * @return array<string, EntityDefinition>
     */
    public function getTriggerEntities(): array
    {
        try {
            return app(RelaticleSchema::class)->getEntities();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Register a model class as a triggerable model for workflows.
     *
     * @param  string  $modelClass  The fully qualified model class name
     * @param  array{label: string, events: list<string>, fields: Closure}  $config  The model configuration
     */
    public function registerTriggerableModel(string $modelClass, array $config): void
    {
        $this->triggerableModels[$modelClass] = $config;

        if (class_exists($modelClass) && is_a($modelClass, \Illuminate\Database\Eloquent\Model::class, true)) {
            $modelClass::observe(WorkflowModelObserver::class);
        }
    }

    /**
     * Get all registered triggerable models.
     *
     * @return array<string, array{label: string, events: list<string>, fields: Closure}>
     */
    public function getTriggerableModels(): array
    {
        return $this->triggerableModels;
    }

    /**
     * Register a custom action class.
     *
     * @param  string  $key  A unique key identifying this action
     * @param  class-string  $actionClass  The fully qualified action class name
     *
     * @throws InvalidArgumentException If the class does not implement WorkflowAction
     */
    public function registerAction(string $key, string $actionClass): void
    {
        if (! is_a($actionClass, WorkflowAction::class, true)) {
            throw new InvalidArgumentException(
                "Action class [{$actionClass}] must implement " . WorkflowAction::class
            );
        }

        $this->actions[$key] = $actionClass;
    }

    /**
     * Get all registered actions (built-in + custom).
     *
     * @return array<string, class-string<WorkflowAction>>
     */
    public function getRegisteredActions(): array
    {
        return $this->getActions();
    }

    /**
     * Configure tenancy scoping for workflows.
     */
    public function useTenancy(string $scopeColumn, Closure $resolver): void
    {
        $this->tenancyConfig = [
            'scopeColumn' => $scopeColumn,
            'resolver' => $resolver,
        ];
    }

    /**
     * Get the tenancy configuration.
     *
     * @return array{scopeColumn: string, resolver: Closure}|null
     */
    public function getTenancyConfig(): ?array
    {
        return $this->tenancyConfig;
    }
}
