<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListTasks
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Task>
     */
    public function execute(User $user, array $filters = []): LengthAwarePaginator
    {
        $user->can('viewAny', Task::class) || abort(403);

        $query = Task::query()->withCustomFieldValues();

        if (isset($filters['search']) && is_string($filters['search'])) {
            $query->where('title', 'ilike', "%{$filters['search']}%");
        }

        if (isset($filters['assigned_to_me']) && $filters['assigned_to_me']) {
            $query->whereHas('assignees', fn ($q) => $q->where('users.id', $user->getKey()));
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
