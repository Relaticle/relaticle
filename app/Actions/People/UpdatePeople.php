<?php

declare(strict_types=1);

namespace App\Actions\People;

use App\Models\People;
use App\Models\User;
use Relaticle\CustomFields\Services\TenantContextService;

final readonly class UpdatePeople
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, People $people, array $data): People
    {
        abort_unless($user->can('update', $people), 403);

        $customFields = $data['custom_fields'] ?? null;
        unset($data['custom_fields']);

        $people->update($data);

        if (is_array($customFields) && $customFields !== []) {
            TenantContextService::withTenant($user->currentTeam->getKey(), function () use ($people, $customFields): void {
                $people->saveCustomFields($customFields);
            });
        }

        return $people->refresh();
    }
}
