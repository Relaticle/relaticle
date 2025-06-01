<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\NoteResource\Forms;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

final class NoteForm
{
    /**
     * @param  Forms\Form  $form  The form instance to modify.
     * @param  array<string>  $excludeFields  Fields to exclude from the form.
     * @return Forms\Form The modified form instance with the schema applied.
     */
    public static function get(Forms\Form $form, array $excludeFields = []): Forms\Form
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
            ->schema($schema)
            ->columns(2);
    }
}
