<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\User\CreatesNewSocialUsers;
use App\Models\User;
use App\Models\UserSocialAccount;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

final class CallbackController
{
    public function __invoke(string $provider, CreatesNewSocialUsers $creator): RedirectResponse
    {
        try {
            $social_user = Socialite::driver($provider)->user();

            // Find Social Account
            $account = UserSocialAccount::query()
                ->where([
                    'provider_name' => $provider,
                    'provider_id' => $social_user->getId(),
                ])
                ->first();

            // If Social Account Exist then Find User and Login
            if ($account) {
                auth()->login($account->user);

                return redirect()->route('dashboard');
            }

            // Find User
            $user = User::whereEmail($social_user->getEmail())->first();

            // If User not get then create new user
            if (! $user) {
                $user = $creator->create([
                    'name' => $social_user->getName(),
                    'email' => $social_user->getEmail(),
                    'terms' => 'on',
                ]);
            }

            // Create Social Account
            $user->socialAccounts()->create([
                'provider_id' => $social_user->getId(),
                'provider_name' => $provider,
            ]);

            // Login
            auth()->login($user);

            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            report($e);

            return redirect()->route('login');
        }
    }
}
