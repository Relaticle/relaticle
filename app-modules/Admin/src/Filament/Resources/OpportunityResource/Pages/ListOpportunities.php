<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\OpportunityResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Relaticle\Admin\Filament\Resources\OpportunityResource;

final class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
