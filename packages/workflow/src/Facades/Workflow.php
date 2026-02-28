<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Facades;

use Illuminate\Support\Facades\Facade;
use Relaticle\Workflow\WorkflowManager;

/**
 * @method static void registerTriggerableModel(string $modelClass, array $config)
 * @method static array getTriggerableModels()
 * @method static void registerAction(string $key, string $actionClass)
 * @method static array getRegisteredActions()
 * @method static void useTenancy(string $scopeColumn, \Closure $resolver)
 * @method static array|null getTenancyConfig()
 *
 * @see \Relaticle\Workflow\WorkflowManager
 */
class Workflow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WorkflowManager::class;
    }
}
