<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\NoteResource\Pages;

use App\Filament\Admin\Resources\NoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewNote extends ViewRecord
{
    protected static string $resource = NoteResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
