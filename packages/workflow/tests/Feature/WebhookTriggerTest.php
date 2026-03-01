<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Jobs\ExecuteWorkflowJob;
use Relaticle\Workflow\Models\Workflow;

it('triggers a webhook workflow via POST', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Webhook Workflow',
        'trigger_type' => TriggerType::Webhook,
        'status' => 'live',
    ]);
    $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);

    $response = $this->postJson("/workflow/api/webhooks/{$workflow->id}", [
        'event' => 'order.completed',
        'data' => ['order_id' => 123],
    ]);

    $response->assertOk();
    Queue::assertPushed(ExecuteWorkflowJob::class, function ($job) use ($workflow) {
        return $job->workflow->id === $workflow->id
            && data_get($job->context, 'webhook.event') === 'order.completed';
    });
});

it('passes full webhook payload as context', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Webhook Workflow',
        'trigger_type' => TriggerType::Webhook,
        'status' => 'live',
    ]);
    $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);

    $payload = ['foo' => 'bar', 'nested' => ['key' => 'value']];
    $this->postJson("/workflow/api/webhooks/{$workflow->id}", $payload);

    Queue::assertPushed(ExecuteWorkflowJob::class, function ($job) {
        return data_get($job->context, 'webhook.foo') === 'bar'
            && data_get($job->context, 'webhook.nested.key') === 'value';
    });
});

it('returns 404 for non-existent webhook workflow', function () {
    Queue::fake();

    $response = $this->postJson('/workflow/api/webhooks/nonexistent-id');

    $response->assertNotFound();
    Queue::assertNothingPushed();
});

it('rejects webhook for inactive workflow', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Inactive Webhook',
        'trigger_type' => TriggerType::Webhook,
        'status' => 'draft',
    ]);

    $response = $this->postJson("/workflow/api/webhooks/{$workflow->id}", ['data' => 'test']);

    $response->assertStatus(422);
    Queue::assertNothingPushed();
});

it('rejects webhook for non-webhook workflow type', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Manual Workflow',
        'trigger_type' => TriggerType::Manual,
        'status' => 'live',
    ]);

    $response = $this->postJson("/workflow/api/webhooks/{$workflow->id}", ['data' => 'test']);

    $response->assertStatus(422);
    Queue::assertNothingPushed();
});

it('rejects webhook with invalid signature', function () {
    $workflow = Workflow::create([
        'name' => 'Signed Webhook',
        'trigger_type' => TriggerType::Webhook,
        'trigger_config' => [],
        'canvas_data' => [],
        'status' => 'live',
        'webhook_secret' => 'test-secret-key',
    ]);

    $response = $this->postJson(
        "/workflow/api/webhooks/{$workflow->id}",
        ['event' => 'test'],
        ['X-Signature' => 'invalid-signature']
    );

    $response->assertStatus(401);
});

it('accepts webhook with valid HMAC signature', function () {
    Queue::fake();

    $secret = 'test-secret-key';
    $workflow = Workflow::create([
        'name' => 'Signed Webhook',
        'trigger_type' => TriggerType::Webhook,
        'trigger_config' => [],
        'canvas_data' => [],
        'status' => 'live',
        'webhook_secret' => $secret,
    ]);
    $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);

    $payload = json_encode(['event' => 'test']);
    $signature = hash_hmac('sha256', $payload, $secret);

    $response = $this->postJson(
        "/workflow/api/webhooks/{$workflow->id}",
        ['event' => 'test'],
        ['X-Signature' => $signature]
    );

    $response->assertOk();
    Queue::assertPushed(ExecuteWorkflowJob::class);
});

it('allows webhook without signature when no secret is configured', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Open Webhook',
        'trigger_type' => TriggerType::Webhook,
        'trigger_config' => [],
        'canvas_data' => [],
        'status' => 'live',
    ]);
    $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);

    $response = $this->postJson(
        "/workflow/api/webhooks/{$workflow->id}",
        ['event' => 'test']
    );

    $response->assertOk();
});
