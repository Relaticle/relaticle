<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaskResource\Forms;

use Filament\Forms;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

class TaskForm
{
    public static function get(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('companies')
                    ->label('Companies')
                    ->multiple()
                    ->relationship('companies', 'name')
                    ->columnSpanFull(),
                Forms\Components\Select::make('people')
                    ->label('People')
                    ->multiple()
                    ->relationship('people', 'name')
                    ->nullable(),
                Forms\Components\Select::make('assignees')
                    ->label('Assignees')
                    ->multiple()
                    ->relationship('assignees', 'name')
                    ->nullable(),
                CustomFieldsComponent::make()->columnSpanFull(),
            ])
            ->columns(2);
    }
}
