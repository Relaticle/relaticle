<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\OpportunityResource\Forms;

use Filament\Forms;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

final class OpportunityForm
{
    public static function get(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->placeholder('Enter opportunity title')
                    ->columnSpanFull(),
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(2),
                Forms\Components\Select::make('contact_id')
                    ->relationship('contact', 'name')
                    ->searchable()
                    ->preload()
                    ->columnSpan(2),
                CustomFieldsComponent::make()->columnSpanFull(),
            ])
            ->columns(4);
    }
}
