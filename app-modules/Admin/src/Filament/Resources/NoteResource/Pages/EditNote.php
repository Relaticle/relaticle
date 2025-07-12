<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\NoteResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Override;
use Relaticle\Admin\Filament\Resources\NoteResource;

final class EditNote extends EditRecord
{
    protected static string $resource = NoteResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
