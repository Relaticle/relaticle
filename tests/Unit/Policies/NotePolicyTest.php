<?php

declare(strict_types=1);

use App\Models\Note;
use App\Models\Team;
use App\Models\User;
use App\Policies\NotePolicy;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->policy = new NotePolicy;
});

describe('NotePolicy Authorization', function () {
    describe('viewAny authorization', function () {
        it('allows verified user with current team to view any notes', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('prevents unverified user from viewing any notes', function () {
            $user = User::factory()->unverified()->create();
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view authorization', function () {
        it('allows team member to view their team note', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $note = Note::factory()->for($team)->make();

            expect($this->policy->view($user, $note))->toBeTrue();
        });

        it('prevents cross-team access to note', function () {
            $user = User::factory()->create();
            $userTeam = Team::factory()->create();
            $noteTeam = Team::factory()->create();
            $user->teams()->attach($userTeam);
            $note = Note::factory()->for($noteTeam)->make();

            expect($this->policy->view($user, $note))->toBeFalse();
        });
    });

    describe('CRUD authorization', function () {
        it('allows verified user to create', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows team member to update note', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $note = Note::factory()->for($team)->make();

            expect($this->policy->update($user, $note))->toBeTrue();
        });

        it('allows team member to delete note', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $note = Note::factory()->for($team)->make();

            expect($this->policy->delete($user, $note))->toBeTrue();
        });
    });

    describe('admin operations', function () {
        it('allows admin to force delete note', function () {
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

        it('prevents non-admin from force delete note', function () {
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

        it('allows admin to force delete any note', function () {
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

        it('prevents non-admin from force delete any note', function () {
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
