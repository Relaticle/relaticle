<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\TaskResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Relaticle\Admin\Filament\Resources\TaskResource;

final class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
