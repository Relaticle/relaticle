<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\TaskResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Relaticle\Admin\Filament\Resources\TaskResource;

final class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
