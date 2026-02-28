<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources\WorkflowResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Relaticle\Workflow\Enums\WorkflowRunStatus;

class RunsRelationManager extends RelationManager
{
    protected static string $relationship = 'runs';

    protected static ?string $title = 'Execution History';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (WorkflowRunStatus $state): string => match ($state) {
                        WorkflowRunStatus::Completed => 'success',
                        WorkflowRunStatus::Failed => 'danger',
                        WorkflowRunStatus::Running => 'info',
                        WorkflowRunStatus::Paused => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('error_message')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->error_message),
            ])
            ->defaultSort('started_at', 'desc');
    }
}
