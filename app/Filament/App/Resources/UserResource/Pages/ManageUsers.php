<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\UserResource\Pages;

use App\Filament\App\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

final class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
