<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaskResource\Forms;

use Filament\Forms;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

final class TaskForm
{
    public static function get(Forms\Form $form, array $excludeFields = []): Forms\Form
    {
        $schema = [
            Forms\Components\TextInput::make('title')
                ->required()
                ->columnSpanFull(),
        ];

        if (! in_array('companies', $excludeFields)) {
            $schema[] = Forms\Components\Select::make('companies')
                ->label('Companies')
                ->multiple()
                ->relationship('companies', 'name')
                ->columnSpanFull();
        }

        if (! in_array('people', $excludeFields)) {
            $schema[] = Forms\Components\Select::make('people')
                ->label('People')
                ->multiple()
                ->relationship('people', 'name')
                ->nullable();
        }

        $schema[] = Forms\Components\Select::make('assignees')
            ->label('Assignees')
            ->multiple()
            ->relationship('assignees', 'name')
            ->nullable();

        $schema[] = CustomFieldsComponent::make()->columnSpanFull();

        return $form
            ->schema($schema)
            ->columns(2);
    }
}
