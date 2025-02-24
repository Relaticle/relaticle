<?php

namespace App\Filament\Admin\Resources\PeopleResource\Pages;

use App\Filament\Admin\Resources\PeopleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePeople extends CreateRecord
{
    protected static string $resource = PeopleResource::class;
}
