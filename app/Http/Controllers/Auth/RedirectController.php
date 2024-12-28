<?php

namespace App\Http\Controllers\Auth;

use App\Enums\SocialiteProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

final class RedirectController
{
    public function __invoke(SocialiteProvider $provider): RedirectResponse
    {
        return Socialite::driver($provider->value)->redirect();
    }
}
