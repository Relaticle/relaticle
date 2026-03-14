<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Note;

use App\Actions\Note\UpdateNote;
use App\Http\Resources\V1\NoteResource;
use App\Mcp\Tools\BaseUpdateTool;
use App\Models\Note;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update an existing note in the CRM. Use the crm-schema resource to discover available custom fields.')]
#[IsIdempotent]
final class UpdateNoteTool extends BaseUpdateTool
{
    protected function modelClass(): string
    {
        return Note::class;
    }

    protected function actionClass(): string
    {
        return UpdateNote::class;
    }

    protected function resourceClass(): string
    {
        return NoteResource::class;
    }

    protected function entityType(): string
    {
        return 'note';
    }

    protected function entityLabel(): string
    {
        return 'note';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('The note title.'),
        ];
    }

    protected function entityRules(User $user): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
