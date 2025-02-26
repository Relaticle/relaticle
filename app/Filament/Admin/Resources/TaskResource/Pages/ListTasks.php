<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TaskResource\Pages;

use App\Filament\Admin\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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
