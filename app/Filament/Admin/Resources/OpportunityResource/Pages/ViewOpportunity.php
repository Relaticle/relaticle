<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OpportunityResource\Pages;

use App\Filament\Admin\Resources\OpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewOpportunity extends ViewRecord
{
    protected static string $resource = OpportunityResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
