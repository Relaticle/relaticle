<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use App\Policies\CompanyPolicy;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->policy = new CompanyPolicy;
});

describe('CompanyPolicy Authorization', function () {
    describe('viewAny authorization', function () {
        it('allows verified user with current team to view any companies', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('prevents unverified user from viewing any companies', function () {
            $user = User::factory()->unverified()->create();
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->viewAny($user))->toBeFalse();
        });

        it('prevents user without team from viewing companies', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $user->setRelation('currentTeam', null);

            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view authorization', function () {
        it('allows team member to view their team company', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $company = Company::factory()->for($team)->make();

            expect($this->policy->view($user, $company))->toBeTrue();
        });

        it('prevents cross-team access to company', function () {
            $user = User::factory()->create();
            $userTeam = Team::factory()->create();
            $companyTeam = Team::factory()->create();
            $user->teams()->attach($userTeam);
            $company = Company::factory()->for($companyTeam)->make();

            expect($this->policy->view($user, $company))->toBeFalse();
        });
    });

    describe('create authorization', function () {
        it('allows verified user with current team to create', function () {
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies unverified user from creating', function () {
            $user = User::factory()->unverified()->create();
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update authorization', function () {
        it('allows team member to update company', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);

            $company = Company::factory()->for($team)->make();

            expect($this->policy->update($user, $company))->toBeTrue();
        });

        it('denies non-team member from updating company', function () {
            $user = User::factory()->create();
            $userTeam = Team::factory()->create();
            $companyTeam = Team::factory()->create();

            $user->teams()->attach($userTeam);
            $company = Company::factory()->for($companyTeam)->make();

            expect($this->policy->update($user, $company))->toBeFalse();
        });
    });

    describe('delete authorization', function () {
        it('allows team member to delete company', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);

            $company = Company::factory()->for($team)->make();

            expect($this->policy->delete($user, $company))->toBeTrue();
        });

        it('denies non-team member from deleting company', function () {
            $user = User::factory()->create();
            $userTeam = Team::factory()->create();
            $companyTeam = Team::factory()->create();

            $user->teams()->attach($userTeam);
            $company = Company::factory()->for($companyTeam)->make();

            expect($this->policy->delete($user, $company))->toBeFalse();
        });
    });

    describe('restore authorization', function () {
        it('allows team member to restore company', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);

            $company = Company::factory()->for($team)->make();

            expect($this->policy->restore($user, $company))->toBeTrue();
        });

        it('denies non-team member from restoring company', function () {
            $user = User::factory()->create();
            $userTeam = Team::factory()->create();
            $companyTeam = Team::factory()->create();

            $user->teams()->attach($userTeam);
            $company = Company::factory()->for($companyTeam)->make();

            expect($this->policy->restore($user, $company))->toBeFalse();
        });
    });

    describe('bulk operations', function () {
        it('allows verified user with current team for deleteAny', function () {
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->deleteAny($user))->toBeTrue();
        });

        it('denies unverified user for deleteAny', function () {
            $user = User::factory()->unverified()->create();
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->deleteAny($user))->toBeFalse();
        });

        it('allows verified user with current team for restoreAny', function () {
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->restoreAny($user))->toBeTrue();
        });

        it('denies unverified user for restoreAny', function () {
            $user = User::factory()->unverified()->create();
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->restoreAny($user))->toBeFalse();
        });
    });

    describe('admin operations', function () {
        it('allows admin to force delete company', function () {
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

        it('prevents non-admin from force delete company', function () {
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

        it('allows admin to force delete any company', function () {
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

        it('prevents non-admin from force delete any company', function () {
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
