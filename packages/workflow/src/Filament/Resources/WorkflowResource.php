<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowStatus;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\WorkflowManager;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bolt';

    protected static string | \UnitEnum | null $navigationGroup = 'Automation';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->maxLength(1000)
                ->columnSpanFull(),
            TextInput::make('folder')
                ->label('Folder')
                ->placeholder('e.g. Sales, Marketing, Onboarding...')
                ->maxLength(100),
            Select::make('trigger_type')
                ->options([
                    TriggerType::RecordEvent->value => 'Record Event',
                    TriggerType::TimeBased->value => 'Time Based',
                    TriggerType::Manual->value => 'Manual',
                    TriggerType::Webhook->value => 'Webhook',
                ])
                ->live()
                ->required(),

            Section::make('Trigger Configuration')
                ->schema([
                    Select::make('trigger_config.model')
                        ->label('Model')
                        ->options(fn () => collect(app(WorkflowManager::class)->getTriggerableModels())
                            ->mapWithKeys(fn ($config, $class) => [$class => $config['label'] ?? class_basename($class)]))
                        ->visible(fn (Get $get) => $get('trigger_type') === TriggerType::RecordEvent->value)
                        ->required(fn (Get $get) => $get('trigger_type') === TriggerType::RecordEvent->value),
                    Select::make('trigger_config.event')
                        ->label('Event')
                        ->options([
                            'created' => 'Created',
                            'updated' => 'Updated',
                            'deleted' => 'Deleted',
                        ])
                        ->visible(fn (Get $get) => $get('trigger_type') === TriggerType::RecordEvent->value)
                        ->required(fn (Get $get) => $get('trigger_type') === TriggerType::RecordEvent->value),
                    TextInput::make('trigger_config.cron')
                        ->label('Cron Expression')
                        ->placeholder('*/5 * * * *')
                        ->visible(fn (Get $get) => $get('trigger_type') === TriggerType::TimeBased->value),
                    TextInput::make('webhook_secret')
                        ->label('Webhook Secret (optional)')
                        ->password()
                        ->revealable()
                        ->visible(fn (Get $get) => $get('trigger_type') === TriggerType::Webhook->value),
                ])
                ->visible(fn (Get $get) => in_array($get('trigger_type'), [
                    TriggerType::RecordEvent->value,
                    TriggerType::TimeBased->value,
                    TriggerType::Webhook->value,
                ])),

            Select::make('status')
                ->options(collect(WorkflowStatus::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)]))
                ->default('draft')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_favorited')
                    ->label('')
                    ->icon(fn (Workflow $record): string => $record->isFavoritedBy(auth()->user()) ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn (Workflow $record): string => $record->isFavoritedBy(auth()->user()) ? 'warning' : 'gray')
                    ->action(fn (Workflow $record) => $record->toggleFavorite(auth()->user()))
                    ->width('40px'),
                TextColumn::make('name')
                    ->description(fn (Workflow $record): ?string => $record->description ?: null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('trigger_type')
                    ->badge()
                    ->formatStateUsing(fn (TriggerType $state): string => $state->getLabel())
                    ->color(fn (TriggerType $state): string => match ($state) {
                        TriggerType::RecordEvent => 'info',
                        TriggerType::TimeBased => 'warning',
                        TriggerType::Manual => 'gray',
                        TriggerType::Webhook => 'success',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (WorkflowStatus $state): string => match ($state) {
                        WorkflowStatus::Draft => 'Draft',
                        WorkflowStatus::Live => 'Live',
                        WorkflowStatus::Paused => 'Paused',
                        WorkflowStatus::Archived => 'Archived',
                    })
                    ->color(fn (WorkflowStatus $state): string => match ($state) {
                        WorkflowStatus::Draft => 'gray',
                        WorkflowStatus::Live => 'success',
                        WorkflowStatus::Paused => 'warning',
                        WorkflowStatus::Archived => 'danger',
                    }),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->onColor('success')
                    ->offColor('gray')
                    ->updateStateUsing(function (Workflow $record, bool $state) {
                        $record->update([
                            'status' => $state ? WorkflowStatus::Live : WorkflowStatus::Paused,
                        ]);
                        return $state;
                    })
                    ->getStateUsing(fn (Workflow $record): bool => $record->status === WorkflowStatus::Live),
                TextColumn::make('folder')
                    ->label('Folder')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('last_triggered_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('runs_count')
                    ->counts('runs')
                    ->label('Runs')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(WorkflowStatus::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])),
                SelectFilter::make('trigger_type')
                    ->options([
                        TriggerType::RecordEvent->value => 'Record Event',
                        TriggerType::TimeBased->value => 'Time Based',
                        TriggerType::Manual->value => 'Manual',
                        TriggerType::Webhook->value => 'Webhook',
                    ]),
                SelectFilter::make('folder')
                    ->options(fn () => Workflow::query()
                        ->whereNotNull('folder')
                        ->distinct()
                        ->pluck('folder', 'folder')
                        ->toArray()
                    )
                    ->placeholder('All folders'),
                TrashedFilter::make(),
            ])
            ->groups([
                Group::make('folder')->label('Folder')->getTitleFromRecordUsing(fn (Workflow $record) => $record->folder ?: 'Uncategorized'),
                'status',
                'trigger_type',
                Group::make('creator.name')->label('Created By'),
            ])
            ->emptyState(function () {
                $templates = \Relaticle\Workflow\Models\WorkflowTemplate::active()
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->limit(8)
                    ->get();

                if ($templates->isEmpty()) {
                    return null;
                }

                return view('workflow::filament.template-empty-state', [
                    'templates' => $templates,
                    'createUrl' => static::getUrl('create'),
                ]);
            })
            ->emptyStateHeading('No workflows found')
            ->emptyStateDescription('Create your first workflow to automate tasks, send notifications, and streamline your CRM processes.')
            ->emptyStateIcon('heroicon-o-bolt')
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Workflow $record): string => static::getUrl('builder', ['record' => $record]))
            ->actions([
                Action::make('open_builder')
                    ->label('Open Builder')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Workflow $record): string => static::getUrl('builder', ['record' => $record])),
                ActionGroup::make([
                    Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->authorize('create', Workflow::class)
                        ->action(function (Workflow $record) {
                            $record->load(['nodes', 'edges']);
                            DB::transaction(function () use ($record) {
                                $new = $record->replicate(['status', 'last_triggered_at']);
                                $new->name = $record->name . ' (copy)';
                                $new->status = WorkflowStatus::Draft;
                                $new->save();

                                $nodeIdMap = [];
                                foreach ($record->nodes as $node) {
                                    $newNode = $node->replicate();
                                    $newNode->workflow_id = $new->id;
                                    $newNode->save();
                                    $nodeIdMap[$node->id] = $newNode->id;
                                }
                                foreach ($record->edges as $edge) {
                                    $newEdge = $edge->replicate();
                                    $newEdge->workflow_id = $new->id;
                                    $newEdge->source_node_id = $nodeIdMap[$edge->source_node_id] ?? $edge->source_node_id;
                                    $newEdge->target_node_id = $nodeIdMap[$edge->target_node_id] ?? $edge->target_node_id;
                                    $newEdge->save();
                                }
                            });
                        })
                        ->successNotificationTitle('Workflow duplicated'),
                    Action::make('archive')
                        ->label('Archive')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Workflow $record): bool => in_array($record->status, [WorkflowStatus::Live, WorkflowStatus::Paused, WorkflowStatus::Draft]))
                        ->action(fn (Workflow $record) => $record->update(['status' => WorkflowStatus::Archived])),
                    RestoreAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => WorkflowResource\Pages\ListWorkflows::route('/'),
            'create' => WorkflowResource\Pages\CreateWorkflow::route('/create'),
            'view' => WorkflowResource\Pages\ViewWorkflow::route('/{record}'),
            'edit' => WorkflowResource\Pages\EditWorkflow::route('/{record}/edit'),
            'builder' => WorkflowResource\Pages\WorkflowBuilder::route('/{record}/builder'),
        ];
    }
}
