<?php

namespace App\Actions\Fortify;

use App\Contracts\User\CreatesNewSocialUsers;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateNewSocialUser extends CreateNewUser implements CreatesNewSocialUsers
{
    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            return tap(User::create([
                'name' => $input['name'],
                'email' => $input['email'],
            ]), function (User $user) {
                $this->createTeam($user);
                if ($user->markEmailAsVerified()) {
                    event(new Verified($user));
                }
            });
        });
    }
}
