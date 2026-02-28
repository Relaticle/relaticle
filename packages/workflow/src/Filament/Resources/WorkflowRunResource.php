<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Relaticle\Workflow\Enums\StepStatus;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Models\WorkflowRun;

class WorkflowRunResource extends Resource
{
    protected static ?string $model = WorkflowRun::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-play';

    protected static string | \UnitEnum | null $navigationGroup = 'Automation';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Run Details')
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (WorkflowRunStatus $state): string => match ($state) {
                                WorkflowRunStatus::Completed => 'success',
                                WorkflowRunStatus::Failed => 'danger',
                                WorkflowRunStatus::Running => 'info',
                                WorkflowRunStatus::Paused => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('workflow.name')
                            ->label('Workflow'),
                        TextEntry::make('started_at')
                            ->dateTime(),
                        TextEntry::make('completed_at')
                            ->dateTime(),
                        TextEntry::make('error_message')
                            ->visible(fn ($record) => filled($record->error_message))
                            ->color('danger'),
                    ])->columns(2),
                Section::make('Execution Steps')
                    ->schema([
                        RepeatableEntry::make('steps')
                            ->schema([
                                TextEntry::make('node.node_id')
                                    ->label('Node'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (StepStatus $state): string => match ($state) {
                                        StepStatus::Completed => 'success',
                                        StepStatus::Failed => 'danger',
                                        StepStatus::Skipped => 'gray',
                                        default => 'info',
                                    }),
                                TextEntry::make('started_at')
                                    ->dateTime(),
                                TextEntry::make('error_message')
                                    ->visible(fn ($record) => filled($record->error_message))
                                    ->color('danger'),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'view' => WorkflowRunResource\Pages\ViewWorkflowRun::route('/{record}'),
        ];
    }
}
