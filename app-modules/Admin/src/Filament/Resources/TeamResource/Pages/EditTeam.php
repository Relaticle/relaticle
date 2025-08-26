<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\TeamResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Relaticle\Admin\Filament\Resources\TeamResource;

final class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
