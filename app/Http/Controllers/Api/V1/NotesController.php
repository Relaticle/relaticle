<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Note\CreateNote;
use App\Actions\Note\DeleteNote;
use App\Actions\Note\ListNotes;
use App\Actions\Note\UpdateNote;
use App\Enums\CreationSource;
use App\Http\Requests\Api\V1\StoreNoteRequest;
use App\Http\Requests\Api\V1\UpdateNoteRequest;
use App\Http\Resources\V1\NoteResource;
use App\Models\Note;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class NotesController
{
    public function index(Request $request, ListNotes $action): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $notes = $action->execute($user, $request->query());

        return NoteResource::collection($notes);
    }

    public function store(StoreNoteRequest $request, CreateNote $action): NoteResource
    {
        /** @var User $user */
        $user = $request->user();

        $note = $action->execute($user, $request->validated(), CreationSource::API);

        return new NoteResource($note->loadMissing('customFieldValues.customField'));
    }

    public function show(Note $note): NoteResource
    {
        return new NoteResource($note->loadMissing('customFieldValues.customField'));
    }

    public function update(UpdateNoteRequest $request, Note $note, UpdateNote $action): NoteResource
    {
        /** @var User $user */
        $user = $request->user();

        $note = $action->execute($user, $note, $request->validated());

        return new NoteResource($note->loadMissing('customFieldValues.customField'));
    }

    public function destroy(Request $request, Note $note, DeleteNote $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->execute($user, $note);

        return response()->json(null, 204);
    }
}
