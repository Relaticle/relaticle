<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company;
use App\Models\User;

final readonly class DeleteCompany
{
    public function execute(User $user, Company $company): void
    {
        $user->can('delete', $company) || abort(403);

        $company->delete();
    }
}
