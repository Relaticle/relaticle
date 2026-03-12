<?php

declare(strict_types=1);

namespace App\Actions\Note;

use App\Enums\CreationSource;
use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class CreateNote
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data, CreationSource $source = CreationSource::WEB): Note
    {
        abort_unless($user->can('create', Note::class), 403);

        $attributes = Arr::only($data, ['title', 'custom_fields']);
        $attributes['creation_source'] = $source;
        $attributes['creator_id'] = $user->getKey();
        $attributes['team_id'] = $user->currentTeam->getKey();

        return DB::transaction(fn (): Note => Note::query()->create($attributes));
    }
}
