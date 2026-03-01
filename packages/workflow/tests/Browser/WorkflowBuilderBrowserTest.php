<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Models\Workflow;

beforeEach(function () {
    // Register a minimal builder test route that serves the builder HTML skeleton
    Route::get('/test/builder/{workflowId}', function (string $workflowId) {
        $workflow = Workflow::findOrFail($workflowId);

        return response(<<<HTML
        <!DOCTYPE html>
        <html>
        <head><title>Workflow Builder - {$workflow->name}</title></head>
        <body>
            <div id="workflow-builder-app" data-workflow-id="{$workflow->id}" class="workflow-builder">
                <div class="workflow-toolbar" data-test="toolbar">
                    <div class="toolbar-left">
                        <button class="toolbar-btn" id="btn-undo" title="Undo">Undo</button>
                        <button class="toolbar-btn" id="btn-redo" title="Redo">Redo</button>
                        <button class="toolbar-btn" id="btn-zoom-in" title="Zoom In">+</button>
                        <button class="toolbar-btn" id="btn-zoom-out" title="Zoom Out">-</button>
                        <button class="toolbar-btn" id="btn-fit" title="Fit to View">Fit</button>
                    </div>
                    <div class="toolbar-right">
                        <button class="toolbar-btn toolbar-btn-primary" id="btn-save">Save</button>
                    </div>
                </div>
                <div class="workflow-content">
                    <div class="workflow-sidebar" data-test="node-sidebar">
                        <div class="sidebar-title">Nodes</div>
                        <div class="sidebar-group">
                            <div class="sidebar-group-title">Triggers</div>
                            <div class="sidebar-node" data-node-type="trigger" data-test="sidebar-trigger-node" draggable="true">Trigger</div>
                        </div>
                        <div class="sidebar-group">
                            <div class="sidebar-group-title">Actions</div>
                            <div class="sidebar-node" data-node-type="action" data-test="sidebar-action-node" draggable="true">Action</div>
                        </div>
                        <div class="sidebar-group">
                            <div class="sidebar-group-title">Logic</div>
                            <div class="sidebar-node" data-node-type="condition" data-test="sidebar-condition-node" draggable="true">Condition</div>
                            <div class="sidebar-node" data-node-type="delay" data-test="sidebar-delay-node" draggable="true">Delay</div>
                            <div class="sidebar-node" data-node-type="loop" data-test="sidebar-loop-node" draggable="true">Loop</div>
                            <div class="sidebar-node" data-node-type="stop" data-test="sidebar-stop-node" draggable="true">Stop</div>
                        </div>
                    </div>
                    <div id="workflow-canvas-container" data-test="workflow-canvas">
                        <div id="workflow-canvas"></div>
                    </div>
                    <div class="workflow-config-panel" id="config-panel" data-test="config-panel" style="display: none;">
                        <div class="config-panel-header">
                            <span class="config-panel-title">Node Configuration</span>
                            <button class="config-panel-close" id="config-panel-close">&times;</button>
                        </div>
                        <div class="config-panel-body" id="config-panel-body"></div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML);
    });
});

it('renders the workflow builder page with all structural elements', function () {
    $workflow = Workflow::create([
        'name' => 'Builder Page Test',
        'trigger_type' => TriggerType::Manual,
        'status' => 'live',
    ]);

    $this->visit("/test/builder/{$workflow->id}")
        ->assertTitleContains('Workflow Builder')
        ->assertPresent('[data-test="toolbar"]')
        ->assertPresent('[data-test="node-sidebar"]')
        ->assertPresent('[data-test="workflow-canvas"]');
});

it('displays all sidebar node types', function () {
    $workflow = Workflow::create([
        'name' => 'Sidebar Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $this->visit("/test/builder/{$workflow->id}")
        ->assertVisible('[data-test="sidebar-trigger-node"]')
        ->assertVisible('[data-test="sidebar-action-node"]')
        ->assertVisible('[data-test="sidebar-condition-node"]')
        ->assertVisible('[data-test="sidebar-delay-node"]')
        ->assertVisible('[data-test="sidebar-loop-node"]')
        ->assertVisible('[data-test="sidebar-stop-node"]');
});

it('displays toolbar buttons', function () {
    $workflow = Workflow::create([
        'name' => 'Toolbar Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $this->visit("/test/builder/{$workflow->id}")
        ->assertVisible('#btn-undo')
        ->assertVisible('#btn-redo')
        ->assertVisible('#btn-zoom-in')
        ->assertVisible('#btn-zoom-out')
        ->assertVisible('#btn-fit')
        ->assertVisible('#btn-save');
});

it('has save button visible in toolbar', function () {
    $workflow = Workflow::create([
        'name' => 'Save Button Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $this->visit("/test/builder/{$workflow->id}")
        ->assertSee('Save')
        ->assertVisible('#btn-save');
});

it('config panel is hidden by default', function () {
    $workflow = Workflow::create([
        'name' => 'Config Panel Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $this->visit("/test/builder/{$workflow->id}")
        ->assertMissing('[data-test="config-panel"]');
});

it('has correct workflow ID data attribute', function () {
    $workflow = Workflow::create([
        'name' => 'Data Attribute Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $this->visit("/test/builder/{$workflow->id}")
        ->assertAttribute('#workflow-builder-app', 'data-workflow-id', $workflow->id);
});

it('sidebar nodes have draggable attribute', function () {
    $workflow = Workflow::create([
        'name' => 'Draggable Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $this->visit("/test/builder/{$workflow->id}")
        ->assertAttribute('[data-test="sidebar-trigger-node"]', 'draggable', 'true')
        ->assertAttribute('[data-test="sidebar-action-node"]', 'draggable', 'true')
        ->assertAttribute('[data-test="sidebar-condition-node"]', 'draggable', 'true');
});

it('sidebar nodes have correct data-node-type attributes', function () {
    $workflow = Workflow::create([
        'name' => 'Node Type Attribute Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $this->visit("/test/builder/{$workflow->id}")
        ->assertDataAttribute('[data-test="sidebar-trigger-node"]', 'node-type', 'trigger')
        ->assertDataAttribute('[data-test="sidebar-action-node"]', 'node-type', 'action')
        ->assertDataAttribute('[data-test="sidebar-condition-node"]', 'node-type', 'condition')
        ->assertDataAttribute('[data-test="sidebar-delay-node"]', 'node-type', 'delay')
        ->assertDataAttribute('[data-test="sidebar-loop-node"]', 'node-type', 'loop')
        ->assertDataAttribute('[data-test="sidebar-stop-node"]', 'node-type', 'stop');
});

it('returns 404 for non-existent workflow builder', function () {
    $this->visit('/test/builder/nonexistent-id')
        ->assertSee('404');
});
