<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Policies\TaskPolicy;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->policy = new TaskPolicy;
});

describe('TaskPolicy Authorization', function () {
    describe('viewAny authorization', function () {
        it('allows verified user with current team to view any tasks', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('prevents unverified user from viewing any tasks', function () {
            $user = User::factory()->unverified()->create();
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view authorization', function () {
        it('allows team member to view their team task', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $task = Task::factory()->for($team)->make();

            expect($this->policy->view($user, $task))->toBeTrue();
        });

        it('prevents cross-team access to task', function () {
            $user = User::factory()->create();
            $userTeam = Team::factory()->create();
            $taskTeam = Team::factory()->create();
            $user->teams()->attach($userTeam);
            $task = Task::factory()->for($taskTeam)->make();

            expect($this->policy->view($user, $task))->toBeFalse();
        });
    });

    describe('CRUD authorization', function () {
        it('allows verified user to create', function () {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $team = Team::factory()->create();
            $user->setRelation('currentTeam', $team);

            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows team member to update task', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $task = Task::factory()->for($team)->make();

            expect($this->policy->update($user, $task))->toBeTrue();
        });

        it('allows team member to delete task', function () {
            $user = User::factory()->create();
            $team = Team::factory()->create();
            $user->teams()->attach($team);
            $task = Task::factory()->for($team)->make();

            expect($this->policy->delete($user, $task))->toBeTrue();
        });
    });

    describe('admin operations', function () {
        it('allows admin to force delete task', function () {
            $adminUser = User::factory()->create();
            $team = Team::factory()->create();
            $task = Task::factory()->for($team)->create();

            // Create admin membership
            $adminUser->teams()->attach($team, ['role' => 'admin']);

            expect($this->policy->forceDelete($adminUser, $task))->toBeTrue();
        });

        it('prevents non-admin from force delete task', function () {
            $memberUser = User::factory()->create();
            $team = Team::factory()->create();
            $task = Task::factory()->for($team)->create();

            // Create regular membership (not admin)
            $memberUser->teams()->attach($team, ['role' => 'member']);

            expect($this->policy->forceDelete($memberUser, $task))->toBeFalse();
        });

        it('allows admin to force delete any task in tenant', function () {
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

        it('prevents non-admin from force delete any task', function () {
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
