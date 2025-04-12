<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PeopleResource\RelationManagers;

use App\Filament\App\Resources\TaskResource\Forms\TaskForm;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Relaticle\CustomFields\Filament\Tables\Columns\CustomFieldsColumn;

final class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $icon = 'heroicon-o-check-circle';

    public function form(Form $form): Form
    {
        return TaskForm::get($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title'),
            ])
            ->pushColumns(CustomFieldsColumn::forRelationManager($this))
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
