<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat\Note;

use App\Actions\Note\CreateNote;
use App\Ai\Tools\Chat\BaseWriteCreateTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;

final class CreateNoteTool extends BaseWriteCreateTool
{
    public function description(): string
    {
        return 'Propose creating a new note. Returns a proposal for user approval.';
    }

    protected function actionClass(): string
    {
        return CreateNote::class;
    }

    protected function entityType(): string
    {
        return 'note';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('The note title.')->required(),
        ];
    }

    protected function extractActionData(Request $request): array
    {
        return ['title' => (string) $request->string('title')];
    }

    protected function buildDisplayData(Request $request): array
    {
        $title = (string) $request->string('title');

        return [
            'title' => 'Create Note',
            'summary' => "Create note \"{$title}\"",
            'fields' => [['label' => 'Title', 'value' => $title]],
        ];
    }
}
