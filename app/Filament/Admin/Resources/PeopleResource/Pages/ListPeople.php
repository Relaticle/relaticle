<?php

namespace App\Filament\Admin\Resources\PeopleResource\Pages;

use App\Filament\Admin\Resources\PeopleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPeople extends ListRecords
{
    protected static string $resource = PeopleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
