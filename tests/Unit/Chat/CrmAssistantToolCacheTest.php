<?php

declare(strict_types=1);

use Relaticle\Chat\Agents\CrmAssistant;
use Tests\TestCase;

uses(TestCase::class);

mutates(CrmAssistant::class);

it('returns the same tool instances across invocations', function (): void {
    $agent = new CrmAssistant;

    $first = $agent->tools();
    $second = $agent->tools();

    expect($second[0])->toBe($first[0]);
});
