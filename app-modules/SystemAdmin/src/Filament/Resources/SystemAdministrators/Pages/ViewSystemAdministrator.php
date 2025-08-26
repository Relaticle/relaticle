<?php

namespace Relaticle\SystemAdmin\Filament\Resources\SystemAdministrators\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Relaticle\SystemAdmin\Filament\Resources\SystemAdministrators\SystemAdministratorResource;

class ViewSystemAdministrator extends ViewRecord
{
    protected static string $resource = SystemAdministratorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
