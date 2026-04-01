<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat\Note;

use App\Actions\Note\UpdateNote;
use App\Ai\Tools\Chat\BaseWriteUpdateTool;
use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Laravel\Ai\Tools\Request;

final class UpdateNoteTool extends BaseWriteUpdateTool
{
    public function description(): string
    {
        return 'Propose updating an existing note. Returns a proposal for user approval.';
    }

    protected function modelClass(): string
    {
        return Note::class;
    }

    protected function actionClass(): string
    {
        return UpdateNote::class;
    }

    protected function entityType(): string
    {
        return 'note';
    }

    protected function entityLabel(): string
    {
        return 'Note';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return ['title' => $schema->string()->description('The new note title.')];
    }

    protected function extractActionData(Request $request): array
    {
        return array_filter(['title' => $request['title'] ?? null], fn (mixed $v): bool => $v !== null);
    }

    protected function buildDisplayData(Request $request, Model $model): array
    {
        $fields = [];
        if (($request['title'] ?? null) !== null) {
            $fields[] = ['label' => 'Title', 'old' => $model->getAttribute('title'), 'new' => $request['title']];
        }

        return [
            'title' => 'Update Note',
            'summary' => "Update note \"{$model->getAttribute('title')}\"",
            'fields' => $fields,
        ];
    }
}
