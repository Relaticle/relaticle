<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools\Note;

use App\Actions\Note\ListNotes;
use Relaticle\Chat\Tools\BaseReadListTool;
use App\Http\Resources\V1\NoteResource;

final class ListNotesTool extends BaseReadListTool
{
    public function description(): string
    {
        return 'List notes with optional search and pagination.';
    }

    protected function actionClass(): string
    {
        return ListNotes::class;
    }

    protected function resourceClass(): string
    {
        return NoteResource::class;
    }

    protected function searchFilterName(): string
    {
        return 'title';
    }
}
