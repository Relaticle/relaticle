<?php

declare(strict_types=1);

namespace App\Actions\Note;

use App\Enums\CreationSource;
use App\Models\Note;
use App\Models\User;
use Relaticle\CustomFields\Services\TenantContextService;

final readonly class CreateNote
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data, CreationSource $source = CreationSource::WEB): Note
    {
        abort_unless($user->can('create', Note::class), 403);

        $customFields = $data['custom_fields'] ?? null;
        unset($data['custom_fields']);

        $data['creation_source'] = $source;
        $data['creator_id'] = $user->getKey();
        $data['team_id'] = $user->currentTeam->getKey();

        $note = Note::query()->create($data);

        if (is_array($customFields) && $customFields !== []) {
            TenantContextService::withTenant($user->currentTeam->getKey(), function () use ($note, $customFields): void {
                $note->saveCustomFields($customFields);
            });
        }

        return $note;
    }
}
