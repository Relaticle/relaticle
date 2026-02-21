<?php

declare(strict_types=1);

namespace App\Actions\People;

use App\Enums\CreationSource;
use App\Models\People;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Relaticle\CustomFields\Services\TenantContextService;

final readonly class CreatePeople
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data, CreationSource $source = CreationSource::WEB): People
    {
        abort_unless($user->can('create', People::class), 403);

        $customFields = $data['custom_fields'] ?? null;
        unset($data['custom_fields']);

        $data['creation_source'] = $source;
        $data['creator_id'] = $user->getKey();
        $data['team_id'] = $user->currentTeam->getKey();

        return DB::transaction(function () use ($user, $data, $customFields): People {
            $people = People::query()->create($data);

            if (is_array($customFields) && $customFields !== []) {
                TenantContextService::withTenant($user->currentTeam->getKey(), function () use ($people, $customFields): void {
                    $people->saveCustomFields($customFields);
                });
            }

            return $people;
        });
    }
}
