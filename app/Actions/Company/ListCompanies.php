<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final readonly class ListCompanies
{
    /**
     * @return LengthAwarePaginator<int, Company>
     */
    public function execute(User $user): LengthAwarePaginator
    {
        abort_unless($user->can('viewAny', Company::class), 403);

        $perPage = min((int) (request()->query('per_page', '15')), 100);

        return QueryBuilder::for(Company::query()->withCustomFieldValues())
            ->allowedFilters([
                AllowedFilter::partial('name'),
            ])
            ->allowedSorts(['name', 'created_at', 'updated_at'])
            ->defaultSort('-created_at')
            ->paginate($perPage);
    }
}
