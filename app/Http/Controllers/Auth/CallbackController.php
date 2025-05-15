<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Contracts\User\CreatesNewSocialUsers;
use App\Models\User;
use App\Models\UserSocialAccount;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

final readonly class CallbackController
{
    public function __invoke(string $provider, CreatesNewSocialUsers $creator): RedirectResponse
    {
        try {
            /** @var AbstractProvider $driver */
            $driver = Socialite::driver($provider);
            $socialUser = $driver->stateless()->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('login')
                ->withErrors(['login' => 'Failed to authenticate with '.ucfirst($provider).'.']);
        }

        // Attempt to find the user via the social account
        $account = UserSocialAccount::with('user')
            ->where('provider_name', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($account) {
            // Log in the user and redirect to the dashboard
            auth()->login($account->user);

            return redirect()->route('dashboard');
        }

        // Attempt to find the user by email if available
        $user = null;
        $email = $socialUser->getEmail();

        if ($email) {
            $user = User::where('email', $email)->first();
        }

        // Create a new user if one doesn't exist
        if (! $user) {
            $user = $creator->create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Unknown User',
                'email' => $email ?? sprintf('%s_%s@noemail.app', $provider, $socialUser->getId()),
                'terms' => 'on',
            ]);
        }

        // Link the social account to the user
        $user->socialAccounts()->updateOrCreate(
            [
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
            ],
        );

        // Log in the user and redirect to the dashboard
        auth()->login($user);

        return redirect()->route('dashboard');
    }
}
