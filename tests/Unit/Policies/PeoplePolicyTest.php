<?php

declare(strict_types=1);

use App\Models\People;
use App\Models\Team;
use App\Models\User;
use App\Policies\PeoplePolicy;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->policy = new PeoplePolicy;
});

describe('PeoplePolicy Authorization', function () {
    describe('viewAny authorization', function () {
        it('allows verified user with current team to view any people', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('prevents unverified user from viewing any people', function () {
            $user = User::factory()->unverified()->create();
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view authorization', function () {
        it('allows team member to view their team people', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $people = People::factory()->for($team)->make();

            expect($this->policy->view($user, $people))->toBeTrue();
        });

        it('prevents cross-team access to people', function () {
            $user = User::factory()->create();
            $userTeam = Team::factory()->create();
            $peopleTeam = Team::factory()->create();
            $user->teams()->attach($userTeam);
            $people = People::factory()->for($peopleTeam)->make();

            expect($this->policy->view($user, $people))->toBeFalse();
        });
    });

    describe('CRUD authorization', function () {
        it('allows verified user to create', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows team member to update people', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $people = People::factory()->for($team)->make();

            expect($this->policy->update($user, $people))->toBeTrue();
        });

        it('allows team member to delete people', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $people = People::factory()->for($team)->make();

            expect($this->policy->delete($user, $people))->toBeTrue();
        });

        it('allows team member to restore people', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $people = People::factory()->for($team)->make();

            expect($this->policy->restore($user, $people))->toBeTrue();
        });
    });

    describe('bulk operations', function () {
        it('allows verified user with team for deleteAny', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->deleteAny($user))->toBeTrue();
        });

        it('allows verified user with team for restoreAny', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->restoreAny($user))->toBeTrue();
        });
    });

    describe('admin operations', function () {
        it('allows admin to force delete people', function () {
            $adminUser = User::factory()->create();
            $team = Team::factory()->create();
            $people = People::factory()->for($team)->create();

            // Create admin membership
            $adminUser->teams()->attach($team, ['role' => 'admin']);

            expect($this->policy->forceDelete($adminUser, $people))->toBeTrue();
        });

        it('prevents non-admin from force delete people', function () {
            $memberUser = User::factory()->create();
            $team = Team::factory()->create();
            $people = People::factory()->for($team)->create();

            // Create regular membership (not admin)
            $memberUser->teams()->attach($team, ['role' => 'member']);

            expect($this->policy->forceDelete($memberUser, $people))->toBeFalse();
        });

        it('allows admin to force delete any people in tenant', function () {
            $adminUser = User::factory()->create();
            $team = Team::factory()->create();

            // Create admin membership
            $adminUser->teams()->attach($team, ['role' => 'admin']);

            // Mock Filament to return the team as current tenant
            Filament::shouldReceive('getTenant')
                ->once()
                ->andReturn($team);

            expect($this->policy->forceDeleteAny($adminUser))->toBeTrue();
        });

        it('prevents non-admin from force delete any people', function () {
            $memberUser = User::factory()->create();
            $team = Team::factory()->create();

            // Create regular membership (not admin)
            $memberUser->teams()->attach($team, ['role' => 'member']);

            // Mock Filament to return the team as current tenant
            Filament::shouldReceive('getTenant')
                ->once()
                ->andReturn($team);

            expect($this->policy->forceDeleteAny($memberUser))->toBeFalse();
        });
    });
});
