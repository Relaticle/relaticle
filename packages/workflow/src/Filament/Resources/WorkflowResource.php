<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Models\Workflow;

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
                ->required(),
            Toggle::make('is_active')
                ->label('Active')
                ->default(false),
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
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
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
