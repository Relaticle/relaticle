<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\TaskResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Relaticle\Admin\Filament\Resources\TaskResource;

final class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
