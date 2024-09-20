<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use ManukMinasyan\FilamentCustomField\Filament\Tables\Concerns\InteractsWithCustomFields;

final class ListUsers extends ListRecords
{
    use InteractsWithCustomFields;

    /**
     * @var class-string<UserResource>
     */
    protected static string $resource = UserResource::class;

    /**
     * Get the actions available on the resource index header.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
