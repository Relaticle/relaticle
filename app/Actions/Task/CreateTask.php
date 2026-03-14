<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\Enums\CreationSource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class CreateTask
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data, CreationSource $source = CreationSource::WEB): Task
    {
        abort_unless($user->can('create', Task::class), 403);

        $attributes = Arr::only($data, ['title', 'custom_fields']);
        $attributes['creation_source'] = $source;

        return DB::transaction(fn (): Task => Task::query()->create($attributes));
    }
}
