<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\CompanyResource\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;
use Relaticle\CustomFields\Filament\Tables\Columns\CustomFieldsColumn;

final class PeopleRelationManager extends RelationManager
{
    protected static string $relationship = 'people';

    protected static ?string $modelLabel = 'person';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-user';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                CustomFields::form()->forModel($schema->getRecord())->build()
                    ->columnSpanFull()
                    ->columns(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
            ])
            ->pushColumns(CustomFieldsColumn::forRelationManager($this))
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
