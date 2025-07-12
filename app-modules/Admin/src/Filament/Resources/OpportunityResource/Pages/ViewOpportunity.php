<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\OpportunityResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Override;
use Relaticle\Admin\Filament\Resources\OpportunityResource;

final class ViewOpportunity extends ViewRecord
{
    protected static string $resource = OpportunityResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
