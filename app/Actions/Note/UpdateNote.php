<?php

declare(strict_types=1);

namespace App\Actions\Note;

use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class UpdateNote
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, Note $note, array $data): Note
    {
        abort_unless($user->can('update', $note), 403);

        $attributes = Arr::only($data, ['title', 'custom_fields']);

        return DB::transaction(function () use ($note, $attributes): Note {
            $note->update($attributes);

            return $note->refresh();
        });
    }
}
