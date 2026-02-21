<?php

declare(strict_types=1);

namespace App\Actions\Opportunity;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Relaticle\CustomFields\Services\TenantContextService;

final readonly class UpdateOpportunity
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, Opportunity $opportunity, array $data): Opportunity
    {
        abort_unless($user->can('update', $opportunity), 403);

        $customFields = $data['custom_fields'] ?? null;
        unset($data['custom_fields']);

        return DB::transaction(function () use ($user, $opportunity, $data, $customFields): Opportunity {
            $opportunity->update($data);

            if (is_array($customFields) && $customFields !== []) {
                TenantContextService::withTenant($user->currentTeam->getKey(), function () use ($opportunity, $customFields): void {
                    $opportunity->saveCustomFields($customFields);
                });
            }

            return $opportunity->refresh();
        });
    }
}
