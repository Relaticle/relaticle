<?php

declare(strict_types=1);

use App\Actions\Company\CreateCompany;
use App\Enums\PendingActionOperation;
use App\Enums\PendingActionStatus;
use App\Models\PendingAction;
use App\Models\User;
use App\Services\AI\PendingActionService;
use Filament\Facades\Filament;

mutates(PendingActionService::class);

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);
    Filament::setTenant($this->team);
    $this->service = app(PendingActionService::class);
});

it('creates a pending action proposal', function (): void {
    $pending = $this->service->createProposal(
        user: $this->user,
        conversationId: 'conv-123',
        actionClass: CreateCompany::class,
        operation: PendingActionOperation::Create,
        entityType: 'company',
        actionData: ['name' => 'Acme Inc'],
        displayData: [
            'title' => 'Create Company',
            'summary' => 'Create company "Acme Inc"',
            'fields' => [['label' => 'Name', 'value' => 'Acme Inc']],
        ],
    );

    expect($pending)
        ->toBeInstanceOf(PendingAction::class)
        ->status->toBe(PendingActionStatus::Pending)
        ->action_class->toBe(CreateCompany::class)
        ->entity_type->toBe('company')
        ->action_data->toBe(['name' => 'Acme Inc'])
        ->and((int) now()->diffInMinutes($pending->expires_at, false))->toBeGreaterThanOrEqual(14);
});

it('approves a pending action and executes the create action', function (): void {
    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-123',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Acme Inc'],
        'display_data' => ['title' => 'Create Company', 'summary' => 'Create company "Acme Inc"', 'fields' => []],
        'status' => PendingActionStatus::Pending,
        'expires_at' => now()->addMinutes(15),
    ]);

    $result = $this->service->approve($pending, $this->user);

    expect($result)->toBeInstanceOf(PendingAction::class)
        ->status->toBe(PendingActionStatus::Approved)
        ->resolved_at->not->toBeNull()
        ->result_data->not->toBeNull();

    $this->assertDatabaseHas('companies', ['name' => 'Acme Inc']);
});

it('rejects a pending action', function (): void {
    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-123',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Acme Inc'],
        'display_data' => ['title' => 'Create Company', 'summary' => 'Create company "Acme Inc"', 'fields' => []],
        'status' => PendingActionStatus::Pending,
        'expires_at' => now()->addMinutes(15),
    ]);

    $result = $this->service->reject($pending, $this->user);

    expect($result->status)->toBe(PendingActionStatus::Rejected)
        ->and($result->resolved_at)->not->toBeNull();

    $this->assertDatabaseMissing('companies', ['name' => 'Acme Inc']);
});

it('cannot approve an expired action', function (): void {
    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-123',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Acme Inc'],
        'display_data' => ['title' => 'Create Company', 'summary' => 'Create company "Acme Inc"', 'fields' => []],
        'status' => PendingActionStatus::Pending,
        'expires_at' => now()->subMinute(),
    ]);

    $this->service->approve($pending, $this->user);
})->throws(RuntimeException::class, 'This action has expired');

it('cannot approve an already resolved action', function (): void {
    $pending = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-123',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Acme Inc'],
        'display_data' => ['title' => 'Create Company', 'summary' => 'Create company "Acme Inc"', 'fields' => []],
        'status' => PendingActionStatus::Approved,
        'expires_at' => now()->addMinutes(15),
        'resolved_at' => now(),
    ]);

    $this->service->approve($pending, $this->user);
})->throws(RuntimeException::class, 'This action has already been resolved');

it('expires stale pending actions', function (): void {
    $stale = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-123',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Stale'],
        'display_data' => [],
        'status' => PendingActionStatus::Pending,
        'expires_at' => now()->subMinutes(5),
    ]);

    $fresh = PendingAction::query()->create([
        'team_id' => $this->team->getKey(),
        'user_id' => $this->user->getKey(),
        'conversation_id' => 'conv-456',
        'action_class' => CreateCompany::class,
        'operation' => PendingActionOperation::Create,
        'entity_type' => 'company',
        'action_data' => ['name' => 'Fresh'],
        'display_data' => [],
        'status' => PendingActionStatus::Pending,
        'expires_at' => now()->addMinutes(10),
    ]);

    $count = $this->service->expireStale();

    expect($count)->toBe(1)
        ->and($stale->refresh()->status)->toBe(PendingActionStatus::Expired)
        ->and($fresh->refresh()->status)->toBe(PendingActionStatus::Pending);
});
