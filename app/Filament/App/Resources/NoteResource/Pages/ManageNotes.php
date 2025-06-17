<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\NoteResource\Pages;

use Override;
use Filament\Actions\CreateAction;
use App\Filament\App\Resources\NoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Relaticle\CustomFields\Filament\Tables\Concerns\InteractsWithCustomFields;

final class ManageNotes extends ManageRecords
{
    use InteractsWithCustomFields;

    protected static string $resource = NoteResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
