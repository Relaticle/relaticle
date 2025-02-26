<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PeopleResource\Pages;

use App\Filament\Admin\Resources\PeopleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePeople extends CreateRecord
{
    protected static string $resource = PeopleResource::class;
}
