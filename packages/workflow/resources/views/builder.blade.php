<x-filament-panels::page>
    <div wire:ignore id="workflow-builder-app" data-workflow-id="{{ $workflowId }}" class="workflow-builder">
        {{-- Toolbar --}}
        <div class="workflow-toolbar" data-test="toolbar">
            <div class="toolbar-left">
                <button class="toolbar-btn" id="btn-undo" title="Undo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 14-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/></svg>
                </button>
                <button class="toolbar-btn" id="btn-redo" title="Redo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 14 4-4-4-4"/><path d="M19 10H8a4 4 0 0 0 0 8h1"/></svg>
                </button>
                <span class="toolbar-separator"></span>
                <button class="toolbar-btn" id="btn-zoom-in" title="Zoom In">+</button>
                <button class="toolbar-btn" id="btn-zoom-out" title="Zoom Out">&minus;</button>
                <button class="toolbar-btn" id="btn-fit" title="Fit to View">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="m21 3-7 7"/><path d="m3 21 7-7"/></svg>
                </button>
            </div>
            <div class="toolbar-right">
                <button class="toolbar-btn toolbar-btn-primary" id="btn-save">Save</button>
            </div>
        </div>

        {{-- Main content --}}
        <div class="workflow-content">
            {{-- Sidebar --}}
            <div class="workflow-sidebar" data-test="node-sidebar">
                <div class="sidebar-title">Nodes</div>
                <div class="sidebar-group">
                    <div class="sidebar-group-title">Triggers</div>
                    <div class="sidebar-node" data-node-type="trigger" data-test="sidebar-trigger-node" draggable="true">
                        <span class="node-icon trigger-icon">&#9889;</span> Trigger
                    </div>
                </div>
                <div class="sidebar-group">
                    <div class="sidebar-group-title">Actions</div>
                    <div class="sidebar-node" data-node-type="action" draggable="true">
                        <span class="node-icon action-icon">&#9654;</span> Action
                    </div>
                </div>
                <div class="sidebar-group">
                    <div class="sidebar-group-title">Logic</div>
                    <div class="sidebar-node" data-node-type="condition" draggable="true">
                        <span class="node-icon condition-icon">&#9670;</span> Condition
                    </div>
                    <div class="sidebar-node" data-node-type="delay" draggable="true">
                        <span class="node-icon delay-icon">&#9201;</span> Delay
                    </div>
                    <div class="sidebar-node" data-node-type="loop" draggable="true">
                        <span class="node-icon loop-icon">&#128260;</span> Loop
                    </div>
                    <div class="sidebar-node" data-node-type="stop" draggable="true">
                        <span class="node-icon stop-icon">&#9209;</span> Stop
                    </div>
                </div>
            </div>

            {{-- Canvas --}}
            <div id="workflow-canvas-container" data-test="workflow-canvas">
                <div id="workflow-canvas"></div>
            </div>

            {{-- Config Panel --}}
            <div class="workflow-config-panel" id="config-panel" data-test="config-panel" style="display: none;">
                <div class="config-panel-header">
                    <span class="config-panel-title">Node Configuration</span>
                    <button class="config-panel-close" id="config-panel-close">&times;</button>
                </div>
                <div class="config-panel-body" id="config-panel-body">
                    {{-- Dynamic content rendered by JS --}}
                </div>
            </div>
        </div>

        {{-- Minimap --}}
        <div id="workflow-minimap"></div>
    </div>

    @push('scripts')
        <script src="{{ asset('vendor/workflow/workflow-builder.js') }}"></script>
    @endpush
    @push('styles')
        <link rel="stylesheet" href="{{ asset('vendor/workflow/workflow-builder.css') }}">
    @endpush
</x-filament-panels::page>
