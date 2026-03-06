<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources\WorkflowRunResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Relaticle\Workflow\Filament\Resources\WorkflowRunResource;

class ViewWorkflowRun extends ViewRecord
{
    protected static string $resource = WorkflowRunResource::class;

    protected function mutateRecord(\Illuminate\Database\Eloquent\Model $record): \Illuminate\Database\Eloquent\Model
    {
        $record->load('steps.node');

        return $record;
    }
}
