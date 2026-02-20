<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Note;

use App\Actions\Note\CreateNote;
use App\Enums\CreationSource;
use App\Http\Resources\V1\NoteResource;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new note in the CRM.')]
final class CreateNoteTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('The note title.')->required(),
        ];
    }

    public function handle(Request $request, CreateNote $action): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $note = $action->execute($user, $validated, CreationSource::MCP);

        return Response::text(
            new NoteResource($note->loadMissing('customFieldValues.customField'))->toJson(JSON_PRETTY_PRINT)
        );
    }
}
