<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Resources\AttributeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use ManukMinasyan\FilamentAttribute\Filament\Resources\AttributeResource;

final class CreateAttribute extends CreateRecord
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
