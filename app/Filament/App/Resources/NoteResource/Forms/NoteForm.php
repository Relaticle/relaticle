<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\NoteResource\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

final class NoteForm
{
    /**
     * @param  Schema  $form  The form instance to modify.
     * @param  array<string>  $excludeFields  Fields to exclude from the form.
     * @return Schema The modified form instance with the schema applied.
     */
    public static function get(Schema $form, array $excludeFields = []): Schema
    {
        $schema = [
            TextInput::make('title')
                ->label('Title')
                ->rules(['max:255'])
                ->columnSpanFull()
                ->required(),
        ];

        if (! in_array('companies', $excludeFields)) {
            $schema[] = Select::make('companies')
                ->label('Companies')
                ->multiple()
                ->relationship('companies', 'name');
        }

        if (! in_array('people', $excludeFields)) {
            $schema[] = Select::make('people')
                ->label('People')
                ->multiple()
                ->relationship('people', 'name')
                ->nullable();
        }

        $schema[] = CustomFieldsComponent::make()
            ->columnSpanFull()
            ->columns();

        return $form
            ->components($schema)
            ->columns(2);
    }
}
