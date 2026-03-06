<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources\WorkflowResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Filament\Resources\WorkflowRunResource;

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
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(function ($record): string {
                        if ($record->started_at && $record->completed_at) {
                            $ms = $record->started_at->diffInMilliseconds($record->completed_at);
                            if ($ms < 1000) {
                                return "{$ms}ms";
                            }
                            $s = round($ms / 1000, 1);
                            return "{$s}s";
                        }
                        return '—';
                    }),
                Tables\Columns\TextColumn::make('steps_count')
                    ->label('Steps')
                    ->counts('steps'),
                Tables\Columns\TextColumn::make('error_message')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->error_message),
            ])
            ->defaultSort('started_at', 'desc')
            ->recordUrl(fn ($record): string => WorkflowRunResource::getUrl('view', ['record' => $record]));
    }
}
