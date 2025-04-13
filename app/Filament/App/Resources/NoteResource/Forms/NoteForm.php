<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\NoteResource\Forms;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

class NoteForm
{
    public static function get(Forms\Form $form): Forms\Form
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
            ])
            ->columns(2);
    }
}
