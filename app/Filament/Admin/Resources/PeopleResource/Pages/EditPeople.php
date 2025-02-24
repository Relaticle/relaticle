<?php

namespace App\Filament\Admin\Resources\PeopleResource\Pages;

use App\Filament\Admin\Resources\PeopleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPeople extends EditRecord
{
    protected static string $resource = PeopleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
