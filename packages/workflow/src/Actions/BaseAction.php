<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Relaticle\Workflow\Actions\Contracts\WorkflowAction;

abstract class BaseAction implements WorkflowAction
{
    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [];
    }

    /**
     * Get the output schema describing what variables this action produces.
     *
     * @return array<string, array{type: string, label: string}>
     */
    public static function outputSchema(): array
    {
        return [];
    }

    /**
     * Get the Filament form components for configuring this action.
     *
     * @return array<\Filament\Forms\Components\Field>
     */
    public static function filamentForm(): array
    {
        return [];
    }

    /**
     * Get the action category for the block picker.
     */
    public static function category(): string
    {
        return 'General';
    }

    /**
     * Get the Heroicon name for this action.
     */
    public static function icon(): string
    {
        return 'heroicon-o-bolt';
    }
}
