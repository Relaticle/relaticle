<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Models\Note;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

final class NoteResource extends Resource
{
    protected static ?string $model = Note::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Workspace';

    #[\Override]
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title'),
                CustomFieldsComponent::make()
                    ->columnSpanFull()
                    ->columns(),
                Select::make('people')
                    ->multiple()
                    ->relationship(titleAttribute: 'name'),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\App\Resources\NoteResource\Pages\ManageNotes::route('/'),
        ];
    }
}
