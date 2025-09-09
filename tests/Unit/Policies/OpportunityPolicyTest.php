<?php

declare(strict_types=1);

use App\Models\Opportunity;
use App\Models\Team;
use App\Models\User;
use App\Policies\OpportunityPolicy;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->policy = new OpportunityPolicy;
});

describe('OpportunityPolicy Authorization', function () {
    describe('viewAny authorization', function () {
        it('allows verified user with current team to view any opportunities', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('prevents unverified user from viewing any opportunities', function () {
            $user = User::factory()->unverified()->create();
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view authorization', function () {
        it('allows team member to view their team opportunity', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $opportunity = Opportunity::factory()->for($team)->make();

            expect($this->policy->view($user, $opportunity))->toBeTrue();
        });

        it('prevents cross-team access to opportunity', function () {
            $user = User::factory()->create();
            $userTeam = Team::factory()->create();
            $opportunityTeam = Team::factory()->create();
            $user->teams()->attach($userTeam);
            $opportunity = Opportunity::factory()->for($opportunityTeam)->make();

            expect($this->policy->view($user, $opportunity))->toBeFalse();
        });
    });

    describe('CRUD authorization', function () {
        it('allows verified user to create', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows team member to update opportunity', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $opportunity = Opportunity::factory()->for($team)->make();

            expect($this->policy->update($user, $opportunity))->toBeTrue();
        });

        it('allows team member to delete opportunity', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $opportunity = Opportunity::factory()->for($team)->make();

            expect($this->policy->delete($user, $opportunity))->toBeTrue();
        });
    });

    describe('admin operations', function () {
        it('allows admin to force delete opportunity', function () {
            $adminUser = User::factory()->create();
            $team = Team::factory()->create();

            // Create admin membership
            $adminUser->teams()->attach($team, ['role' => 'admin']);

            // Mock Filament to return the team as current tenant
            Filament::shouldReceive('getTenant')
                ->once()
                ->andReturn($team);

            expect($this->policy->forceDelete($adminUser))->toBeTrue();
        });

        it('prevents non-admin from force delete opportunity', function () {
            $memberUser = User::factory()->create();
            $team = Team::factory()->create();

            // Create regular membership (not admin)
            $memberUser->teams()->attach($team, ['role' => 'member']);

            // Mock Filament to return the team as current tenant
            Filament::shouldReceive('getTenant')
                ->once()
                ->andReturn($team);

            expect($this->policy->forceDelete($memberUser))->toBeFalse();
        });

        it('allows admin to force delete any opportunity', function () {
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

        it('prevents non-admin from force delete any opportunity', function () {
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
