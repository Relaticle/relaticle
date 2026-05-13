<?php

declare(strict_types=1);

use App\Actions\Company\CreateCompany;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Relaticle\Chat\Enums\PendingActionOperation;
use Relaticle\Chat\Events\AiWriteCompleted;
use Relaticle\Chat\Jobs\ContinueChatMessage;
use Relaticle\Chat\Services\PendingActionService;

mutates(PendingActionService::class);

beforeEach(function (): void {
    Bus::fake([ContinueChatMessage::class]);

    $this->user = User::factory()->withPersonalTeam()->create();
    $this->user->switchTeam($this->user->ownedTeams()->first());
    $this->actingAs($this->user);
    Filament::setTenant($this->user->currentTeam);
});

it('dispatches an AiWriteCompleted event after a Create approval', function (): void {
    Event::fake([AiWriteCompleted::class]);

    $conversationId = (string) Str::uuid7();
    DB::table('agent_conversations')->insert([
        'id' => $conversationId,
        'user_id' => $this->user->getKey(),
        'team_id' => $this->user->currentTeam->getKey(),
        'title' => '',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = app(PendingActionService::class);

    $pending = $service->createProposal(
        user: $this->user,
        conversationId: $conversationId,
        actionClass: CreateCompany::class,
        operation: PendingActionOperation::Create,
        entityType: 'company',
        actionData: [
            'name' => 'Refresh Co',
            'account_owner_id' => $this->user->getKey(),
        ],
        displayData: [
            'title' => 'Create Company',
            'summary' => 'Create company "Refresh Co"',
            'fields' => [['label' => 'Name', 'value' => 'Refresh Co']],
        ],
    );

    $service->approve($pending, $this->user);

    $teamId = (string) $this->user->currentTeam->getKey();

    Event::assertDispatched(AiWriteCompleted::class, fn (AiWriteCompleted $event): bool => $event->teamId === $teamId
        && $event->entityType === 'company'
        && $event->operation === 'create');
});
