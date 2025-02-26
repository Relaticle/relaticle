<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\NoteResource\Pages;

use App\Filament\Admin\Resources\NoteResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateNote extends CreateRecord
{
    protected static string $resource = NoteResource::class;
}
