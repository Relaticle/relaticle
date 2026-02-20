<?php

declare(strict_types=1);

namespace App\Actions\Note;

use App\Models\Note;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final readonly class ListNotes
{
    /**
     * @return LengthAwarePaginator<int, Note>
     */
    public function execute(User $user): LengthAwarePaginator
    {
        abort_unless($user->can('viewAny', Note::class), 403);

        $perPage = min((int) (request()->query('per_page', '15')), 100);

        return QueryBuilder::for(Note::query()->withCustomFieldValues())
            ->allowedFilters([
                AllowedFilter::partial('title'),
            ])
            ->allowedSorts(['title', 'created_at', 'updated_at'])
            ->defaultSort('-created_at')
            ->paginate($perPage);
    }
}
