<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources\WorkflowResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Relaticle\Workflow\Filament\Resources\WorkflowResource;

class ViewWorkflow extends ViewRecord
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('builder')
                ->label('Open Builder')
                ->icon('heroicon-o-paint-brush')
                ->url(fn () => WorkflowResource::getUrl('builder', ['record' => $this->record])),
            Actions\EditAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            \Relaticle\Workflow\Filament\Resources\WorkflowResource\RelationManagers\RunsRelationManager::class,
        ];
    }
}
