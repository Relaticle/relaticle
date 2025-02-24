<?php

namespace App\Filament\Admin\Resources\NoteResource\Pages;

use App\Filament\Admin\Resources\NoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNote extends EditRecord
{
    protected static string $resource = NoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
