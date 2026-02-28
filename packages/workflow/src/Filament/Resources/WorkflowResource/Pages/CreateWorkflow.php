<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources\WorkflowResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Relaticle\Workflow\Filament\Resources\WorkflowResource;

class CreateWorkflow extends CreateRecord
{
    protected static string $resource = WorkflowResource::class;
}
