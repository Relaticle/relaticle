<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

final class RedirectController
{
    public function __invoke(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }
}
