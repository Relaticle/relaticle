<?php

declare(strict_types=1);

use App\Actions\Company\CreateCompany;
use App\Actions\Company\DeleteCompany;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Relaticle\Chat\Enums\PendingActionOperation;
use Relaticle\Chat\Enums\PendingActionStatus;
use Relaticle\Chat\Http\Controllers\PendingActionController;
use Relaticle\Chat\Models\PendingAction;
use Relaticle\Chat\Services\PendingActionService;

mutates(PendingActionController::class);

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);
    Filament::setTenant($this->team);
});

it('approves a pending action and creates the record', function (): void {
    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-123',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Test Corp'],
        'display_data' => ['title' => 'Create Company', 'summary' => 'Create company "Test Corp"', 'fields' => []],
        'status' => PendingActionStatus::Pending,
        'expires_at' => now()->addMinutes(15),
    ]);

    $this->postJson(route('chat.actions.approve', $pending))
        ->assertOk()
        ->assertJsonPath('status', 'approved');

    $this->assertDatabaseHas('companies', ['name' => 'Test Corp']);
});

it('rejects a pending action without creating the record', function (): void {
    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-123',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Rejected Corp'],
        'display_data' => ['title' => 'Create Company', 'summary' => 'Create company "Rejected Corp"', 'fields' => []],
        'status' => PendingActionStatus::Pending,
        'expires_at' => now()->addMinutes(15),
    ]);

    $this->postJson(route('chat.actions.reject', $pending))
        ->assertOk()
        ->assertJsonPath('status', 'rejected');

    $this->assertDatabaseMissing('companies', ['name' => 'Rejected Corp']);
});

it('returns 422 for expired actions', function (): void {
    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-123',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Expired Corp'],
        'display_data' => [],
        'status' => PendingActionStatus::Pending,
        'expires_at' => now()->subMinutes(5),
    ]);

    $this->postJson(route('chat.actions.approve', $pending))
        ->assertUnprocessable()
        ->assertJsonPath('error', 'This action has expired');
});

it('returns 422 for already resolved actions', function (): void {
    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-123',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Already Done Corp'],
        'display_data' => [],
        'status' => PendingActionStatus::Approved,
        'expires_at' => now()->addMinutes(15),
        'resolved_at' => now(),
    ]);

    $this->postJson(route('chat.actions.approve', $pending))
        ->assertUnprocessable()
        ->assertJsonPath('error', 'This action has already been resolved');
});

it('returns 404 for actions from another team', function (): void {
    $otherUser = User::factory()->withPersonalTeam()->create();

    $pending = PendingAction::query()->create([
        'team_id' => $otherUser->currentTeam->getKey(),
        'user_id' => $otherUser->getKey(),
        'conversation_id' => 'conv-other',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Other Team Corp'],
        'display_data' => [],
        'status' => PendingActionStatus::Pending,
        'expires_at' => now()->addMinutes(15),
    ]);

    $this->postJson(route('chat.actions.approve', $pending))
        ->assertNotFound();
});

it('returns 404 for actions belonging to another user on same team', function (): void {
    $otherUser = User::factory()->create();
    $this->team->users()->attach($otherUser, ['role' => 'member']);

    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $otherUser->getKey(),
        'conversation_id' => 'conv-other-user',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Not My Action'],
        'display_data' => [],
        'status' => PendingActionStatus::Pending,
        'expires_at' => now()->addMinutes(15),
    ]);

    $this->postJson(route('chat.actions.approve', $pending))
        ->assertNotFound();
});

it('rejects unauthenticated approve request', function (): void {
    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-123',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Unauth Corp'],
        'display_data' => [],
        'status' => PendingActionStatus::Pending,
        'expires_at' => now()->addMinutes(15),
    ]);

    auth()->logout();

    $this->postJson(route('chat.actions.approve', $pending))
        ->assertUnauthorized();
});

it('rejects are idempotent under second call', function (): void {
    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-x',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Acme'],
        'display_data' => [],
        'status' => PendingActionStatus::Pending,
        'expires_at' => now()->addMinutes(15),
    ]);

    $service = app(PendingActionService::class);

    $service->reject($pending);

    expect(fn () => $service->reject($pending->refresh()))->toThrow(RuntimeException::class);
});

it('restores a soft-deleted record within the undo window', function (): void {
    $company = Company::factory()->for($this->team)->create(['name' => 'Restore Me Corp']);
    $company->delete();

    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-restore',
        'action_class' => DeleteCompany::class,
        'operation' => PendingActionOperation::Delete,
        'entity_type' => 'company',
        'action_data' => [
            '_record_id' => $company->getKey(),
            '_model_class' => Company::class,
        ],
        'display_data' => [],
        'status' => PendingActionStatus::Approved,
        'expires_at' => now()->addMinutes(15),
        'resolved_at' => now()->subMinute(),
    ]);

    $this->postJson(route('chat.actions.restore', $pending))
        ->assertOk()
        ->assertJsonPath('status', 'restored')
        ->assertJsonPath('record.id', (string) $company->getKey())
        ->assertJsonPath('record.type', 'company');

    expect($company->fresh()->trashed())->toBeFalse();
    expect($pending->fresh()->status)->toBe(PendingActionStatus::Restored);
});

it('rejects restore after the 5-minute window', function (): void {
    $company = Company::factory()->for($this->team)->create();
    $company->delete();

    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-restore-late',
        'action_class' => DeleteCompany::class,
        'operation' => PendingActionOperation::Delete,
        'entity_type' => 'company',
        'action_data' => [
            '_record_id' => $company->getKey(),
            '_model_class' => Company::class,
        ],
        'display_data' => [],
        'status' => PendingActionStatus::Approved,
        'expires_at' => now()->addMinutes(15),
        'resolved_at' => now()->subMinutes(10),
    ]);

    $this->postJson(route('chat.actions.restore', $pending))
        ->assertStatus(410)
        ->assertJsonPath('error', 'undo_window_expired');

    expect($company->fresh()->trashed())->toBeTrue();
});

it('rejects restore for non-delete operations', function (): void {
    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-restore-create',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Something'],
        'display_data' => [],
        'status' => PendingActionStatus::Approved,
        'expires_at' => now()->addMinutes(15),
        'resolved_at' => now()->subMinute(),
    ]);

    $this->postJson(route('chat.actions.restore', $pending))
        ->assertUnprocessable()
        ->assertJsonPath('error', 'Only deleted records can be restored');
});

it('rejects restore for cross-team users', function (): void {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $company = Company::factory()->for($otherUser->currentTeam)->create();
    $company->delete();

    $pending = PendingAction::query()->create([
        'team_id' => $otherUser->currentTeam->getKey(),
        'user_id' => $otherUser->getKey(),
        'conversation_id' => 'conv-restore-cross',
        'action_class' => DeleteCompany::class,
        'operation' => PendingActionOperation::Delete,
        'entity_type' => 'company',
        'action_data' => [
            '_record_id' => $company->getKey(),
            '_model_class' => Company::class,
        ],
        'display_data' => [],
        'status' => PendingActionStatus::Approved,
        'expires_at' => now()->addMinutes(15),
        'resolved_at' => now()->subMinute(),
    ]);

    $this->postJson(route('chat.actions.restore', $pending))
        ->assertNotFound();

    expect($company->fresh()->trashed())->toBeTrue();
});
