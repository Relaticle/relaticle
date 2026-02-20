<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Note;

use App\Actions\Note\UpdateNote;
use App\Http\Resources\V1\NoteResource;
use App\Models\Note;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update an existing note in the CRM.')]
#[IsIdempotent]
final class UpdateNoteTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The note ID to update.')->required(),
            'title' => $schema->string()->description('The note title.'),
        ];
    }

    public function handle(Request $request, UpdateNote $action): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'id' => ['required', 'string'],
            'title' => ['sometimes', 'string', 'max:255'],
        ]);

        /** @var Note $note */
        $note = Note::query()->findOrFail($validated['id']);
        unset($validated['id']);

        $note = $action->execute($user, $note, $validated);

        return Response::text(
            new NoteResource($note->loadMissing('customFieldValues.customField'))->toJson(JSON_PRETTY_PRINT)
        );
    }
}
