<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Relaticle\Admin\Filament\Resources\UserResource;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
