<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\PeopleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Relaticle\Admin\Filament\Resources\PeopleResource;

final class CreatePeople extends CreateRecord
{
    protected static string $resource = PeopleResource::class;
}
