<?php

declare(strict_types=1);

namespace App\Listeners\Email;

use App\Models\User;
use Illuminate\Auth\Events\Login;

final class RecordLoginTimestampListener
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user instanceof User) {
            $user->forceFill(['last_login_at' => now()])->saveQuietly();
        }
    }
}
