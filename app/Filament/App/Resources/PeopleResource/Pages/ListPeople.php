<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PeopleResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\PeopleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Relaticle\CustomFields\Filament\Tables\Concerns\InteractsWithCustomFields;

final class ListPeople extends ListRecords
{
    use InteractsWithCustomFields;

    protected static string $resource = PeopleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
