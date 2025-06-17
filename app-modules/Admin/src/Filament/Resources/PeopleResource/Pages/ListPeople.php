<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\PeopleResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;
use Relaticle\Admin\Filament\Resources\PeopleResource;

final class ListPeople extends ListRecords
{
    protected static string $resource = PeopleResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
