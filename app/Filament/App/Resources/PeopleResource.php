<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\PeopleResource\RelationManagers\TasksRelationManager;
use App\Filament\Resources\PeopleResource\Pages;
use App\Models\People;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

class PeopleResource extends Resource
{
    protected static ?string $model = People::class;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * The navigation icon for the resource.
     */
    protected static ?string $navigationIcon = 'heroicon-m-user';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Workspace';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                CustomFieldsComponent::make()
                    ->columnSpanFull()
                    ->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TasksRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\App\Resources\PeopleResource\Pages\ListPeople::route('/'),
            'create' => \App\Filament\App\Resources\PeopleResource\Pages\CreatePeople::route('/create'),
            'edit' => \App\Filament\App\Resources\PeopleResource\Pages\EditPeople::route('/{record}/edit'),
        ];
    }
}
