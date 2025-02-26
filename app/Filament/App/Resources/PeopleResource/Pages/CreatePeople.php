<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PeopleResource\Pages;

use App\Filament\App\Resources\PeopleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePeople extends CreateRecord
{
    protected static string $resource = PeopleResource::class;
}
