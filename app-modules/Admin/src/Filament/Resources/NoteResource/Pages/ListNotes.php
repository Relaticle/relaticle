<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\NoteResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;
use Relaticle\Admin\Filament\Resources\NoteResource;

final class ListNotes extends ListRecords
{
    protected static string $resource = NoteResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
