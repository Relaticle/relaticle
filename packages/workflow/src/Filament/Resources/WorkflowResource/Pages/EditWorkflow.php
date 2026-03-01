<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources\WorkflowResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Relaticle\Workflow\Filament\Resources\WorkflowResource;

class EditWorkflow extends EditRecord
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('builder')
                ->label('Open Builder')
                ->icon('heroicon-o-cube-transparent')
                ->url(fn () => WorkflowResource::getUrl('builder', ['record' => $this->record])),
            DeleteAction::make(),
        ];
    }
}
