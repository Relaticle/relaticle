<?php

declare(strict_types=1);

use Relaticle\Chat\Agents\CrmAssistant;

mutates(CrmAssistant::class);

it('does not instruct the assistant to surface record IDs to the user', function (): void {
    $instructions = (new CrmAssistant)->instructions();

    expect($instructions)->not->toContain('always include the record ID');
});

it('explicitly forbids surfacing record IDs in user-visible output', function (): void {
    $instructions = (new CrmAssistant)->instructions();

    expect($instructions)->toContain('Never expose record IDs to the user');
});
