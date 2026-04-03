<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Enums\OnboardingReferralSource;
use App\Enums\OnboardingUseCase;
use App\Models\Team;
use App\Models\User;
use App\Rules\ValidTeamSlug;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Events\AddingTeam;
use Laravel\Jetstream\Jetstream;

final readonly class CreateTeam implements CreatesTeams
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(User $user, array $input): Team
    {
        Gate::forUser($user)->authorize('create', Jetstream::newTeamModel());

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', new ValidTeamSlug, 'unique:teams,slug'],
            'onboarding_use_case' => ['nullable', 'string', Rule::enum(OnboardingUseCase::class)],
            'onboarding_context' => ['nullable', 'array'],
            'onboarding_context.*' => ['string'],
            'onboarding_referral_source' => ['nullable', 'string', Rule::enum(OnboardingReferralSource::class)],
        ])->validateWithBag('createTeam');

        $isFirstTeam = ! $user->ownedTeams()->exists();

        event(new AddingTeam($user));

        $user->switchTeam($team = $user->ownedTeams()->create([
            'name' => $input['name'],
            'slug' => $input['slug'],
            'personal_team' => $isFirstTeam,
            'onboarding_use_case' => $input['onboarding_use_case'] ?? null,
            'onboarding_context' => $input['onboarding_context'] ?? null,
            'onboarding_referral_source' => $input['onboarding_referral_source'] ?? null,
        ]));

        /** @var Team $team */
        return $team;
    }
}
