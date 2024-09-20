<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use ManukMinasyan\FilamentCustomField\Filament\Tables\Concerns\InteractsWithCustomFields;

class ListOpportunities extends ListRecords
{
    use InteractsWithCustomFields;

    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
