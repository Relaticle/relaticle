<?php

declare(strict_types=1);

namespace App\Actions\Note;

use App\Models\Note;
use App\Models\User;
use Relaticle\CustomFields\Services\TenantContextService;

final readonly class UpdateNote
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, Note $note, array $data): Note
    {
        abort_unless($user->can('update', $note), 403);

        $customFields = $data['custom_fields'] ?? null;
        unset($data['custom_fields']);

        $note->update($data);

        if (is_array($customFields) && $customFields !== []) {
            TenantContextService::withTenant($user->currentTeam->getKey(), function () use ($note, $customFields): void {
                $note->saveCustomFields($customFields);
            });
        }

        return $note->refresh();
    }
}
