<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Relaticle\Chat\Jobs\ProcessChatMessage;

mutates(ProcessChatMessage::class);

it('returns the message unchanged when no mentions are present', function (): void {
    $job = new ProcessChatMessage(
        user: new User,
        team: new Team,
        message: 'hello',
        conversationId: '019dded5-0000-7000-8000-000000000000',
        resolved: ['provider' => 'anthropic', 'model' => 'claude-haiku-4-5'],
        mentions: [],
    );

    $reflection = new ReflectionMethod($job, 'buildAugmentedMessage');
    $reflection->setAccessible(true);

    expect($reflection->invoke($job))->toBe('hello');
});

it('prepends a context block when mentions are present', function (): void {
    $job = new ProcessChatMessage(
        user: new User,
        team: new Team,
        message: 'Tell me about @Acme_Corp',
        conversationId: '019dded5-0000-7000-8000-000000000000',
        resolved: ['provider' => 'anthropic', 'model' => 'claude-haiku-4-5'],
        mentions: [
            ['type' => 'company', 'id' => '01H8QWERTYUIOP1234567890AB', 'label' => 'Acme Corp'],
        ],
    );

    $reflection = new ReflectionMethod($job, 'buildAugmentedMessage');
    $reflection->setAccessible(true);

    $result = $reflection->invoke($job);

    expect($result)
        ->toContain('<context>')
        ->toContain('company "Acme Corp" (id: 01H8QWERTYUIOP1234567890AB)')
        ->toContain('</context>')
        ->toContain('Tell me about @Acme_Corp');
});
