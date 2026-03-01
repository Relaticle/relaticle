<?php

declare(strict_types=1);

use Relaticle\Workflow\Actions\SendEmailAction;
use Relaticle\Workflow\Actions\HttpRequestAction;
use Relaticle\Workflow\Actions\SendWebhookAction;
use Relaticle\Workflow\Actions\DelayAction;
use Relaticle\Workflow\Actions\LoopAction;
use Relaticle\Workflow\Actions\BaseAction;

it('returns output schema for SendEmailAction', function () {
    $schema = SendEmailAction::outputSchema();

    expect($schema)->toHaveKeys(['sent', 'to'])
        ->and($schema['sent']['type'])->toBe('boolean')
        ->and($schema['to']['type'])->toBe('string');
});

it('returns output schema for HttpRequestAction', function () {
    $schema = HttpRequestAction::outputSchema();

    expect($schema)->toHaveKeys(['status_code', 'success', 'response_body'])
        ->and($schema['status_code']['type'])->toBe('number')
        ->and($schema['success']['type'])->toBe('boolean');
});

it('returns output schema for SendWebhookAction', function () {
    $schema = SendWebhookAction::outputSchema();

    expect($schema)->toHaveKeys(['status_code', 'success', 'response_body']);
});

it('returns output schema for DelayAction', function () {
    $schema = DelayAction::outputSchema();

    expect($schema)->toHaveKeys(['delayed', 'delay_seconds'])
        ->and($schema['delayed']['type'])->toBe('boolean')
        ->and($schema['delay_seconds']['type'])->toBe('number');
});

it('returns output schema for LoopAction', function () {
    $schema = LoopAction::outputSchema();

    expect($schema)->toHaveKeys(['item_count'])
        ->and($schema['item_count']['type'])->toBe('number');
});

it('includes outputSchema in canvas API response', function () {
    $workflow = \Relaticle\Workflow\Models\Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => \Relaticle\Workflow\Enums\TriggerType::Manual,
        'status' => 'draft',
    ]);

    $response = $this->getJson("/workflow/api/workflows/{$workflow->id}/canvas");

    $response->assertOk();
    $meta = $response->json('meta');
    expect($meta)->toHaveKey('registered_actions')
        ->and($meta)->toHaveKey('trigger_outputs');

    // Check that registered actions include outputSchema
    foreach ($meta['registered_actions'] as $key => $action) {
        expect($action)->toHaveKey('outputSchema');
    }
});
