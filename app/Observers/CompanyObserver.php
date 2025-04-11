<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\FetchFaviconForCompany;
use App\Models\Company;

final class CompanyObserver
{
    /**
     * Handle the Company "creating" event.
     */
    public function creating(Company $company): void
    {
        $company->creator_id = auth()->id();
    }

    /**
     * Handle the Company "updated" event.
     */
    public function saved(Company $company): void
    {
        FetchFaviconForCompany::dispatch($company);
    }
}
