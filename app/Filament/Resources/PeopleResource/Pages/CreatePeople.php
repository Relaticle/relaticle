<?php

namespace App\Filament\Resources\PeopleResource\Pages;

use App\Filament\Resources\PeopleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePeople extends CreateRecord
{
    protected static string $resource = PeopleResource::class;
}
