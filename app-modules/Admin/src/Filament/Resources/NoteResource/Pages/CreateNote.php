<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\NoteResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Relaticle\Admin\Filament\Resources\NoteResource;

final class CreateNote extends CreateRecord
{
    protected static string $resource = NoteResource::class;
}
