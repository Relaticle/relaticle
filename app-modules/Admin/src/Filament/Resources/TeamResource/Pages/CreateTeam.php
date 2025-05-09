<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\TeamResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Relaticle\Admin\Filament\Resources\TeamResource;

final class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;
}
