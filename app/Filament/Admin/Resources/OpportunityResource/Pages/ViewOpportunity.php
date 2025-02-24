<?php

namespace App\Filament\Admin\Resources\OpportunityResource\Pages;

use App\Filament\Admin\Resources\OpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOpportunity extends ViewRecord
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
