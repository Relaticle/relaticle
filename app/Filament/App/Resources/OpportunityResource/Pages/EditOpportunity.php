<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\OpportunityResource\Pages;

use App\Filament\App\Resources\OpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditOpportunity extends EditRecord
{
    protected static string $resource = OpportunityResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
