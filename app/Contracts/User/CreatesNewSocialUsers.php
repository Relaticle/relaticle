<?php

declare(strict_types=1);

namespace App\Contracts\User;

use App\Models\User;

interface CreatesNewSocialUsers
{
    /**
     * Create a new social user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User;
}
