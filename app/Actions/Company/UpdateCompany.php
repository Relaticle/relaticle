<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company;
use App\Models\User;
use Relaticle\CustomFields\Services\TenantContextService;

final readonly class UpdateCompany
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, Company $company, array $data): Company
    {
        abort_unless($user->can('update', $company), 403);

        $customFields = $data['custom_fields'] ?? null;
        unset($data['custom_fields']);

        $company->update($data);

        if (is_array($customFields) && $customFields !== []) {
            TenantContextService::withTenant($user->currentTeam->getKey(), function () use ($company, $customFields): void {
                $company->saveCustomFields($customFields);
            });
        }

        return $company->refresh();
    }
}
