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
            Actions\Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->visible(fn ($record) => $record->status !== \Relaticle\Workflow\Enums\WorkflowStatus::Live)
                ->requiresConfirmation()
                ->action(function ($record) {
                    $errors = $record->getActivationErrors();
                    if (!empty($errors)) {
                        \Filament\Notifications\Notification::make()
                            ->title('Cannot publish workflow')
                            ->body(implode("\n", $errors))
                            ->danger()
                            ->send();
                        return;
                    }
                    $record->update([
                        'status' => \Relaticle\Workflow\Enums\WorkflowStatus::Live,
                        'published_at' => now(),
                    ]);
                    \Filament\Notifications\Notification::make()
                        ->title('Workflow published')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('pause')
                ->label('Pause')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->visible(fn ($record) => $record->status === \Relaticle\Workflow\Enums\WorkflowStatus::Live)
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['status' => \Relaticle\Workflow\Enums\WorkflowStatus::Paused]);
                    \Filament\Notifications\Notification::make()
                        ->title('Workflow paused')
                        ->warning()
                        ->send();
                }),
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
