<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Jobs\ExecuteWorkflowJob;
use Relaticle\Workflow\Models\Workflow;

it('triggers a webhook workflow via browser fetch POST', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Webhook Browser Test',
        'trigger_type' => TriggerType::Webhook,
        'is_active' => true,
    ]);
    $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);

    $url = "/workflow/api/webhooks/{$workflow->id}";

    $result = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$url}', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({event:'order.completed',data:{order_id:123}})}); return await r.json(); })()");

    expect($result['message'])->toBe('Webhook processed successfully.');

    Queue::assertPushed(ExecuteWorkflowJob::class, function ($job) use ($workflow) {
        return $job->workflow->id === $workflow->id
            && data_get($job->context, 'webhook.event') === 'order.completed';
    });
});

it('passes full webhook payload as context via browser', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Webhook Payload Test',
        'trigger_type' => TriggerType::Webhook,
        'is_active' => true,
    ]);
    $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);

    $url = "/workflow/api/webhooks/{$workflow->id}";

    $result = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$url}', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({foo:'bar',nested:{key:'value'}})}); return await r.json(); })()");

    expect($result['message'])->toBe('Webhook processed successfully.');

    Queue::assertPushed(ExecuteWorkflowJob::class, function ($job) {
        return data_get($job->context, 'webhook.foo') === 'bar'
            && data_get($job->context, 'webhook.nested.key') === 'value';
    });
});

it('returns error for inactive webhook workflow via browser', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Inactive Webhook Browser',
        'trigger_type' => TriggerType::Webhook,
        'is_active' => false,
    ]);

    $url = "/workflow/api/webhooks/{$workflow->id}";

    $result = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$url}', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({data:'test'})}); return await r.json(); })()");

    expect($result['error'])->toContain('not active');
    Queue::assertNothingPushed();
});

it('returns error for non-webhook workflow type via browser', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Manual Not Webhook',
        'trigger_type' => TriggerType::Manual,
        'is_active' => true,
    ]);

    $url = "/workflow/api/webhooks/{$workflow->id}";

    $result = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$url}', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({data:'test'})}); return await r.json(); })()");

    expect($result['error'])->toContain('not a webhook trigger');
    Queue::assertNothingPushed();
});
