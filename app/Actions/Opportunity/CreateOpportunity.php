<?php

declare(strict_types=1);

namespace App\Actions\Opportunity;

use App\Enums\CreationSource;
use App\Models\Opportunity;
use App\Models\User;

final readonly class CreateOpportunity
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data, CreationSource $source = CreationSource::WEB): Opportunity
    {
        $user->can('create', Opportunity::class) || abort(403);

        $data['creation_source'] = $source;
        $data['creator_id'] = $user->getKey();
        $data['team_id'] = $user->currentTeam->getKey();

        return Opportunity::create($data);
    }
}
