<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\NoteResource\Pages;

use App\Filament\App\Resources\NoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Override;
use Relaticle\CustomFields\Concerns\InteractsWithCustomFields;

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
