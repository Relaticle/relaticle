<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use App\Policies\TeamPolicy;

beforeEach(function () {
    $this->policy = new TeamPolicy;
});

describe('TeamPolicy Authorization', function () {
    describe('viewAny authorization', function () {
        it('allows verified user with current team to view any teams', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('prevents unverified user from viewing any teams', function () {
            $user = User::factory()->unverified()->create();
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->viewAny($user))->toBeFalse();
        });

        it('prevents user without team from viewing teams', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $user->setRelation('currentTeam', null);

            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view authorization', function () {
        it('allows team member to view their team', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);

            expect($this->policy->view($user, $team))->toBeTrue();
        });

        it('prevents non-team member from viewing team', function () {
            $user = User::factory()->create();
            $userTeam = Team::factory()->create();
            $otherTeam = Team::factory()->create();
            $user->teams()->attach($userTeam);

            expect($this->policy->view($user, $otherTeam))->toBeFalse();
        });
    });

    describe('create authorization', function () {
        it('allows user to create team when under limit', function () {
            $user = User::factory()->create();
            // User has 0 owned teams, limit is 3

            expect($this->policy->create($user))->toBeTrue();
        });

        it('prevents user from creating team when at limit', function () {
            $user = User::factory()->create();

            // Create 3 teams owned by user to reach limit
            Team::factory()->count(3)->create(['user_id' => $user->id]);
            $user->load('ownedTeams');

            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('ownership-based authorization', function () {
        it('allows team owner to update team', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create(['user_id' => $user->id]);

            expect($this->policy->update($user, $team))->toBeTrue();
        });

        it('prevents non-owner from updating team', function () {
            $owner = User::factory()->create();
            $member = User::factory()->create();
            $team = Team::factory()->create(['user_id' => $owner->id]);

            expect($this->policy->update($member, $team))->toBeFalse();
        });

        it('allows team owner to delete team', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create(['user_id' => $user->id]);

            expect($this->policy->delete($user, $team))->toBeTrue();
        });

        it('allows team owner to add team members', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create(['user_id' => $user->id]);

            expect($this->policy->addTeamMember($user, $team))->toBeTrue();
        });

        it('allows team owner to update team member permissions', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create(['user_id' => $user->id]);

            expect($this->policy->updateTeamMember($user, $team))->toBeTrue();
        });

        it('allows team owner to remove team members', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create(['user_id' => $user->id]);

            expect($this->policy->removeTeamMember($user, $team))->toBeTrue();
        });

        it('allows team owner to restore team', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create(['user_id' => $user->id]);

            expect($this->policy->restore($user, $team))->toBeTrue();
        });

        it('allows team owner to force delete team', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create(['user_id' => $user->id]);

            expect($this->policy->forceDelete($user, $team))->toBeTrue();
        });
    });

    describe('restricted operations', function () {
        it('prevents bulk delete operations', function () {
            expect($this->policy->deleteAny())->toBeFalse();
        });

        it('prevents bulk restore operations', function () {
            expect($this->policy->restoreAny())->toBeFalse();
        });

        it('prevents bulk force delete operations', function () {
            expect($this->policy->forceDeleteAny())->toBeFalse();
        });
    });
});
