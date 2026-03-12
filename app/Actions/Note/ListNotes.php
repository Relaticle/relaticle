<?php

declare(strict_types=1);

namespace App\Actions\Note;

use App\Models\Note;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final readonly class ListNotes
{
    /**
     * @param  array<string, mixed>  $filters
     * @return CursorPaginator<int, Note>|LengthAwarePaginator<int, Note>
     */
    public function execute(
        User $user,
        int $perPage = 15,
        bool $useCursor = false,
        array $filters = [],
        ?int $page = null,
        ?Request $request = null,
    ): CursorPaginator|LengthAwarePaginator {
        abort_unless($user->can('viewAny', Note::class), 403);

        $perPage = max(1, min($perPage, 100));

        $request ??= new Request(['filter' => $filters]);

        $query = QueryBuilder::for(Note::query()->withCustomFieldValues(), $request)
            ->allowedFilters([
                AllowedFilter::partial('title'),
            ])
            ->allowedFields(['id', 'title', 'creator_id', 'created_at', 'updated_at'])
            ->allowedIncludes(['creator', 'companies', 'people', 'opportunities'])
            ->allowedSorts(['title', 'created_at', 'updated_at'])
            ->defaultSort('-created_at');

        if ($useCursor) {
            return $query->cursorPaginate($perPage);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
