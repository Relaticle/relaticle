<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Contracts\User\CreatesNewSocialUsers;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class CreateNewSocialUser implements CreatesNewSocialUsers
{
    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws \Throwable
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        ])->validate();

        return DB::transaction(fn () => tap(User::create([
            'name' => $input['name'],
            'email' => $input['email'],
        ]), function (User $user): void {
            $this->createTeam($user);
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }));
    }

    /**
     * Create a personal team for the user.
     */
    private function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }
}
