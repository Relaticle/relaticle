<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\PeopleResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Relaticle\Admin\Filament\Resources\PeopleResource;

final class ViewPeople extends ViewRecord
{
    protected static string $resource = PeopleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
