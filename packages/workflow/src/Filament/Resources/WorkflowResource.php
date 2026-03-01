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
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
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
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('trigger_type')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (WorkflowStatus $state): string => match ($state) {
                        WorkflowStatus::Draft => 'gray',
                        WorkflowStatus::Live => 'success',
                        WorkflowStatus::Paused => 'warning',
                        WorkflowStatus::Archived => 'danger',
                    }),
                TextColumn::make('last_triggered_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('runs_count')
                    ->counts('runs')
                    ->label('Runs'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('trigger_type')
                    ->options([
                        TriggerType::RecordEvent->value => 'Record Event',
                        TriggerType::TimeBased->value => 'Time Based',
                        TriggerType::Manual->value => 'Manual',
                        TriggerType::Webhook->value => 'Webhook',
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
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
