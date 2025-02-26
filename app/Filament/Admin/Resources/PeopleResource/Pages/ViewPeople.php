<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PeopleResource\Pages;

use App\Filament\Admin\Resources\PeopleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewPeople extends ViewRecord
{
    protected static string $resource = PeopleResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
