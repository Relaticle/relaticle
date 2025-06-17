<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\OpportunityResource\Forms;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

final class OpportunityForm
{
    public static function get(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->placeholder('Enter opportunity title')
                    ->columnSpanFull(),
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(2),
                Select::make('contact_id')
                    ->relationship('contact', 'name')
                    ->searchable()
                    ->preload()
                    ->columnSpan(2),
                CustomFieldsComponent::make()->columnSpanFull(),
            ])
            ->columns(4);
    }
}
