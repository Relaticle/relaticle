<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Jobs\ExecuteWorkflowJob;
use Relaticle\Workflow\Models\Workflow;

it('triggers a manual workflow via browser fetch POST', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Manual Browser Test',
        'trigger_type' => TriggerType::Manual,
        'is_active' => true,
    ]);
    $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);

    $url = "/workflow/api/workflows/{$workflow->id}/trigger";

    $result = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$url}', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'}}); return await r.json(); })()");

    expect($result)->toBeArray();
    expect($result['message'])->toBe('Workflow triggered successfully.');

    Queue::assertPushed(ExecuteWorkflowJob::class);
});

it('returns error for inactive workflow via browser fetch', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Inactive Browser Test',
        'trigger_type' => TriggerType::Manual,
        'is_active' => false,
    ]);

    $url = "/workflow/api/workflows/{$workflow->id}/trigger";

    $result = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$url}', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'}}); return await r.json(); })()");

    expect($result)->toBeArray();
    expect($result['error'])->toContain('not active');

    Queue::assertNothingPushed();
});

it('returns error for non-manual workflow via browser fetch', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Non-Manual Browser Test',
        'trigger_type' => TriggerType::RecordEvent,
        'is_active' => true,
    ]);

    $url = "/workflow/api/workflows/{$workflow->id}/trigger";

    $result = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$url}', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'}}); return await r.json(); })()");

    expect($result)->toBeArray();
    expect($result['error'])->toContain('not a manual trigger');

    Queue::assertNothingPushed();
});

it('passes context data via browser fetch POST', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'Context Browser Test',
        'trigger_type' => TriggerType::Manual,
        'is_active' => true,
    ]);
    $workflow->nodes()->create(['node_id' => 'trigger', 'type' => NodeType::Trigger]);

    $url = "/workflow/api/workflows/{$workflow->id}/trigger";

    $result = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$url}', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({context:{record:{name:'Acme Corp'}}})}); return await r.json(); })()");

    expect($result['message'])->toBe('Workflow triggered successfully.');

    Queue::assertPushed(ExecuteWorkflowJob::class, function ($job) {
        return data_get($job->context, 'record.name') === 'Acme Corp';
    });
});
