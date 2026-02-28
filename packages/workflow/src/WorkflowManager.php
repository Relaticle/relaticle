<?php

declare(strict_types=1);

namespace Relaticle\Workflow;

use Closure;
use InvalidArgumentException;
use Relaticle\Workflow\Actions\Contracts\WorkflowAction;
use Relaticle\Workflow\Observers\WorkflowModelObserver;

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
     * Register a model class as a triggerable model for workflows.
     *
     * @param  string  $modelClass  The fully qualified model class name
     * @param  array{label: string, events: list<string>, fields: Closure}  $config  The model configuration
     */
    public function registerTriggerableModel(string $modelClass, array $config): void
    {
        $this->triggerableModels[$modelClass] = $config;

        // Dynamically attach the observer so workflows fire on model events.
        // This is done eagerly at registration time because the host app
        // typically registers models after the service provider boots.
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
     * Get all registered actions.
     *
     * @return array<string, class-string<WorkflowAction>>
     */
    public function getRegisteredActions(): array
    {
        return $this->actions;
    }

    /**
     * Configure tenancy scoping for workflows.
     *
     * @param  string  $scopeColumn  The database column used for tenant scoping
     * @param  Closure  $resolver  A closure that resolves the current tenant identifier
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
