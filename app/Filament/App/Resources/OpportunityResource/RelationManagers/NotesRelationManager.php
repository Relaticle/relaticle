<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\OpportunityResource\RelationManagers;

use App\Filament\App\Resources\NoteResource\Forms\NoteForm;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

final class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $icon = 'heroicon-o-document-text';

    public function form(Form $form): Form
    {
        return NoteForm::get($form, ['opportunities']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
