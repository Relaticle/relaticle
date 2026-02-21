<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CreationSource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Relaticle\CustomFields\Services\TenantContextService;

final readonly class CreateCompany
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data, CreationSource $source = CreationSource::WEB): Company
    {
        abort_unless($user->can('create', Company::class), 403);

        $customFields = $data['custom_fields'] ?? null;
        unset($data['custom_fields']);

        $data['creation_source'] = $source;
        $data['creator_id'] = $user->getKey();
        $data['team_id'] = $user->currentTeam->getKey();

        return DB::transaction(function () use ($user, $data, $customFields): Company {
            $company = Company::query()->create($data);

            if (is_array($customFields) && $customFields !== []) {
                TenantContextService::withTenant($user->currentTeam->getKey(), function () use ($company, $customFields): void {
                    $company->saveCustomFields($customFields);
                });
            }

            return $company;
        });
    }
}
