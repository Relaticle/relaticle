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
                        <span class="node-icon trigger-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span> Trigger
                    </div>
                </div>
                <div class="sidebar-group">
                    <div class="sidebar-group-title">Actions</div>
                    <div class="sidebar-node" data-node-type="action" draggable="true">
                        <span class="node-icon action-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg></span> Action
                    </div>
                </div>
                <div class="sidebar-group">
                    <div class="sidebar-group-title">Logic</div>
                    <div class="sidebar-node" data-node-type="condition" draggable="true">
                        <span class="node-icon condition-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg></span> Condition
                    </div>
                    <div class="sidebar-node" data-node-type="delay" draggable="true">
                        <span class="node-icon delay-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></span> Delay
                    </div>
                    <div class="sidebar-node" data-node-type="loop" draggable="true">
                        <span class="node-icon loop-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg></span> Loop
                    </div>
                    <div class="sidebar-node" data-node-type="stop" draggable="true">
                        <span class="node-icon stop-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg></span> Stop
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
