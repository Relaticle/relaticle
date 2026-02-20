<?php

declare(strict_types=1);

namespace App\Actions\Opportunity;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final readonly class ListOpportunities
{
    /**
     * @return LengthAwarePaginator<int, Opportunity>
     */
    public function execute(User $user): LengthAwarePaginator
    {
        abort_unless($user->can('viewAny', Opportunity::class), 403);

        $perPage = min((int) (request()->query('per_page', '15')), 100);

        return QueryBuilder::for(Opportunity::query()->withCustomFieldValues())
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::exact('company_id'),
            ])
            ->allowedSorts(['name', 'created_at', 'updated_at'])
            ->defaultSort('-created_at')
            ->paginate($perPage);
    }
}
