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
use Relaticle\Workflow\Models\WorkflowRunStep;

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
                        TextEntry::make('duration')
                            ->label('Total Duration')
                            ->getStateUsing(function (WorkflowRun $record): string {
                                if ($record->started_at && $record->completed_at) {
                                    $ms = (int) $record->started_at->diffInMilliseconds($record->completed_at);
                                    if ($ms < 1000) {
                                        return "{$ms}ms";
                                    }
                                    $s = round($ms / 1000, 1);

                                    return "{$s}s";
                                }

                                return '—';
                            }),
                        TextEntry::make('error_message')
                            ->visible(fn (WorkflowRun $record): bool => filled($record->error_message))
                            ->color('danger')
                            ->columnSpanFull(),
                    ])->columns(3),

                Section::make('Execution Steps')
                    ->schema([
                        RepeatableEntry::make('steps')
                            ->schema([
                                TextEntry::make('node_label')
                                    ->label('Step')
                                    ->getStateUsing(fn (WorkflowRunStep $record): string => self::getStepLabel($record))
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (StepStatus $state): string => match ($state) {
                                        StepStatus::Completed => 'success',
                                        StepStatus::Failed => 'danger',
                                        StepStatus::Skipped => 'gray',
                                        default => 'info',
                                    }),
                                TextEntry::make('duration_display')
                                    ->label('Duration')
                                    ->getStateUsing(fn (WorkflowRunStep $record): string => match (true) {
                                        $record->duration_ms !== null && $record->duration_ms < 1000 => "{$record->duration_ms}ms",
                                        $record->duration_ms !== null => round($record->duration_ms / 1000, 1) . 's',
                                        default => '—',
                                    }),
                                TextEntry::make('started_at')
                                    ->dateTime()
                                    ->label('Started'),
                                TextEntry::make('error_message')
                                    ->visible(fn (WorkflowRunStep $record): bool => filled($record->error_message))
                                    ->color('danger')
                                    ->columnSpanFull(),
                                TextEntry::make('input_display')
                                    ->label('Input')
                                    ->getStateUsing(fn (WorkflowRunStep $record): string => self::formatJsonField($record->input_data))
                                    ->visible(fn (WorkflowRunStep $record): bool => ! empty($record->input_data))
                                    ->fontFamily('mono')
                                    ->copyable()
                                    ->columnSpanFull(),
                                TextEntry::make('output_display')
                                    ->label('Output')
                                    ->getStateUsing(fn (WorkflowRunStep $record): string => self::formatJsonField($record->output_data))
                                    ->visible(fn (WorkflowRunStep $record): bool => ! empty($record->output_data))
                                    ->fontFamily('mono')
                                    ->copyable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(4),
                    ]),

                Section::make('Context Data')
                    ->schema([
                        TextEntry::make('context_display')
                            ->label('')
                            ->getStateUsing(fn (WorkflowRun $record): string => self::formatJsonField($record->context_data))
                            ->visible(fn (WorkflowRun $record): bool => ! empty($record->context_data))
                            ->fontFamily('mono')
                            ->copyable()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'view' => WorkflowRunResource\Pages\ViewWorkflowRun::route('/{record}'),
        ];
    }

    /**
     * Get a human-readable label for a workflow run step based on its node type/action.
     */
    private static function getStepLabel(WorkflowRunStep $record): string
    {
        $node = $record->node;
        if ($node === null) {
            return 'Unknown Step';
        }

        return match ($node->type?->value) {
            'trigger' => 'Trigger',
            'condition' => 'If / Else',
            'delay' => 'Delay',
            'loop' => 'Loop',
            'stop' => 'Stop',
            'action' => self::getActionLabel($node->action_type),
            default => ucfirst($node->type?->value ?? 'Unknown'),
        };
    }

    /**
     * Convert an action_type slug to a human-readable label.
     */
    private static function getActionLabel(?string $actionType): string
    {
        if ($actionType === null) {
            return 'Action';
        }

        return ucwords(str_replace('_', ' ', $actionType));
    }

    /**
     * Format array/JSON data as a readable string.
     */
    private static function formatJsonField(mixed $data): string
    {
        if (empty($data)) {
            return '(none)';
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '(none)';
    }
}
