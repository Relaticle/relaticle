<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaskResource\Pages;

use App\Filament\App\Resources\TaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Relaticle\CustomFields\Concerns\InteractsWithCustomFields;

final class ManageTasks extends ManageRecords
{
    use InteractsWithCustomFields;

    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver(),
        ];
    }
}
