<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Models\Note;
use Filament\Forms\Components\DateTimePicker;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label('Title')
                    ->rules(['max:255'])
                    ->columnSpanFull()
                    ->required(),
                CustomFieldsComponent::make()
                    ->columnSpanFull()
                    ->columns(),
                Select::make('companies')
                    ->label('Companies')
                    ->multiple()
                    ->relationship('companies', 'name'),
                Select::make('people')
                    ->label('People')
                    ->multiple()
                    ->relationship('people', 'name')
                    ->nullable(),

                DateTimePicker::make('created_at')
                    ->label('Created At')
                    ->readOnly(),
                DateTimePicker::make('updated_at')
                    ->label('Updated At')
                    ->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('companies.name')
                    ->label('Companies')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('people.name')
                    ->label('People')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
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
