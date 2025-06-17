<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaskResource\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

final class TaskForm
{
    /**
     * @param  array<string>  $excludeFields
     */
    public static function get(Schema $form, array $excludeFields = []): Schema
    {
        $schema = [
            TextInput::make('title')
                ->required()
                ->columnSpanFull(),
        ];

        if (! in_array('companies', $excludeFields)) {
            $schema[] = Select::make('companies')
                ->label('Companies')
                ->multiple()
                ->relationship('companies', 'name')
                ->columnSpanFull();
        }

        if (! in_array('people', $excludeFields)) {
            $schema[] = Select::make('people')
                ->label('People')
                ->multiple()
                ->relationship('people', 'name')
                ->nullable();
        }

        $schema[] = Select::make('assignees')
            ->label('Assignees')
            ->multiple()
            ->relationship('assignees', 'name')
            ->nullable();

        $schema[] = CustomFieldsComponent::make()->columnSpanFull();

        return $form
            ->components($schema)
            ->columns(2);
    }
}
