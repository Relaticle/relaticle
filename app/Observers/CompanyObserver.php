<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\FetchFaviconForCompany;
use App\Models\Company;

final readonly class CompanyObserver
{
    /**
     * Handle the Company "creating" event.
     */
    public function creating(Company $company): void
    {
        if (auth()->check()) {
            $company->creator_id = auth()->id();
            $company->team_id = auth()->user()->currentTeam->getKey();
        }
    }

    /**
     * Handle the Company "updated" event.
     */
    public function saved(Company $company): void
    {
        FetchFaviconForCompany::dispatch($company)->afterCommit();
    }
}
