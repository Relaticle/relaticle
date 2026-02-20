<?php

declare(strict_types=1);

namespace App\Actions\Opportunity;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListOpportunities
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Opportunity>
     */
    public function execute(User $user, array $filters = []): LengthAwarePaginator
    {
        $user->can('viewAny', Opportunity::class) || abort(403);

        $query = Opportunity::query()->withCustomFieldValues();

        if (isset($filters['search']) && is_string($filters['search'])) {
            $query->where('name', 'ilike', "%{$filters['search']}%");
        }

        if (isset($filters['company_id']) && is_string($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
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
