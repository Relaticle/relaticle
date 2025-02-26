<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PeopleResource\Pages;

use App\Filament\Admin\Resources\PeopleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListPeople extends ListRecords
{
    protected static string $resource = PeopleResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
