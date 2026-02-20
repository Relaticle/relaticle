<?php

declare(strict_types=1);

namespace App\Actions\Note;

use App\Models\Note;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListNotes
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Note>
     */
    public function execute(User $user, array $filters = []): LengthAwarePaginator
    {
        abort_unless($user->can('viewAny', Note::class), 403);

        $query = Note::query()->withCustomFieldValues();

        if (isset($filters['search']) && is_string($filters['search'])) {
            $query->where('title', 'ilike', "%{$filters['search']}%");
        }

        if (isset($filters['sort']) && is_string($filters['sort'])) {
            $direction = ($filters['sort_direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            $query->orderBy($filters['sort'], $direction);
        } else {
            $query->latest();
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }
}
