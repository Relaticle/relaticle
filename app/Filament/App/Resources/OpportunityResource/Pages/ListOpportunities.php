<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\OpportunityResource\Pages;

use App\Filament\App\Resources\OpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Relaticle\CustomFields\Filament\Tables\Concerns\InteractsWithCustomFields;

final class ListOpportunities extends ListRecords
{
    use InteractsWithCustomFields;

    protected static string $resource = OpportunityResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
