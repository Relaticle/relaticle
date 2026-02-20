<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CreationSource;
use App\Models\Company;
use App\Models\User;

final readonly class CreateCompany
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data, CreationSource $source = CreationSource::WEB): Company
    {
        $user->can('create', Company::class) || abort(403);

        $data['creation_source'] = $source;
        $data['creator_id'] = $user->getKey();
        $data['team_id'] = $user->currentTeam->getKey();

        return Company::create($data);
    }
}
