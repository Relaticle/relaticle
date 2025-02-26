<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PeopleResource\Pages;

use App\Filament\App\Resources\PeopleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditPeople extends EditRecord
{
    protected static string $resource = PeopleResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
