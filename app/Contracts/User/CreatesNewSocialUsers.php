<?php

declare(strict_types=1);

namespace App\Contracts\User;

use App\Models\User;

interface CreatesNewSocialUsers
{
    public function create(array $input): User;
}
