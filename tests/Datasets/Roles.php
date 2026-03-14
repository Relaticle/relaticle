<?php

declare(strict_types=1);

use App\Models\User;

dataset('roles', [
    'owner' => fn () => User::factory()->withPersonalTeam()->create(),
    'admin' => fn () => User::factory()->withTeam()->create(),
]);
