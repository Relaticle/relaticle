<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions\Contracts;

interface WorkflowAction
{
    /**
     * Execute the action with the given configuration and context.
     *
     * @param  array<string, mixed>  $config  The action configuration
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>  The action output
     */
    public function execute(array $config, array $context): array;

    /**
     * Get a human-readable label for this action.
     */
    public static function label(): string;

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array;

    /**
     * Get the output schema describing what variables this action produces.
     *
     * @return array<string, array{type: string, label: string}>
     */
    public static function outputSchema(): array;
}
