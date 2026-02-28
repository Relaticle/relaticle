<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources\WorkflowResource\Pages;

use Filament\Resources\Pages\Page;
use Relaticle\Workflow\Filament\Resources\WorkflowResource;

class WorkflowBuilder extends Page
{
    protected static string $resource = WorkflowResource::class;

    protected string $view = 'workflow::builder';

    public ?string $workflowId = null;

    public function mount(string $record): void
    {
        $this->workflowId = $record;
    }

    public function getTitle(): string
    {
        return 'Workflow Builder';
    }
}
