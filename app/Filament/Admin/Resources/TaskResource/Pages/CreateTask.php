<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TaskResource\Pages;

use App\Filament\Admin\Resources\TaskResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;
}
