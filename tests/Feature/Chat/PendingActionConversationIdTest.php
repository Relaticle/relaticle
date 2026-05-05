<?php

declare(strict_types=1);

use App\Actions\Company\CreateCompany;
use App\Models\User;
use Filament\Facades\Filament;
use Relaticle\Chat\Enums\PendingActionOperation;
use Relaticle\Chat\Services\PendingActionService;
use Relaticle\Chat\Tools\Company\CreateCompanyTool;

it('persists the active conversation id on pending actions created from tools', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);
    Filament::setTenant($user->currentTeam);

    $conversationId = '01957a1a-5b02-7a01-83b6-c2b7d9b6f1aa';

    /** @var CreateCompanyTool $tool */
    $tool = app(CreateCompanyTool::class);
    $tool->setConversationId($conversationId);

    $service = app(PendingActionService::class);
    $pending = $service->createProposal(
        user: $user,
        conversationId: $conversationId,
        actionClass: CreateCompany::class,
        operation: PendingActionOperation::Create,
        entityType: 'company',
        actionData: ['name' => 'Acme'],
        displayData: ['title' => 'Create Company'],
    );

    expect($pending->conversation_id)->toBe($conversationId);
});
