<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources\WorkflowResource\Pages;

use Filament\Resources\Pages\Page;
use Relaticle\Workflow\Filament\Resources\WorkflowResource;
use Relaticle\Workflow\Models\Workflow;

class WorkflowBuilder extends Page
{
    protected static string $resource = WorkflowResource::class;
    protected string $view = 'workflow::builder';
    public ?string $workflowId = null;
    public ?string $workflowStatus = null;
    public ?string $workflowName = null;

    public function mount(string $record): void
    {
        $this->workflowId = $record;
        $workflow = Workflow::findOrFail($record);
        $this->workflowStatus = $workflow->status->value;
        $this->workflowName = $workflow->name;
    }

    public function getTitle(): string
    {
        return $this->workflowName ?? 'Workflow Builder';
    }
}
