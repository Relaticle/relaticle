<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Resources\AttributeResource\Pages;

use ManukMinasyan\FilamentAttribute\Filament\Resources\AttributeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditAttribute extends EditRecord
{
    protected static string $resource = AttributeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['lookup_type'] ??= null;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
