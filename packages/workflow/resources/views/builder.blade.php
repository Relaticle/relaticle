<x-filament-panels::page>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('vendor/workflow/workflow-builder.css') }}">
    @endpush

    <div
        x-data="workflowBuilder('{{ $workflowId }}', '{{ $workflowStatus ?? 'draft' }}', {{ json_encode($workflowName ?? '') }})"
        class="workflow-builder"
    >
        {{-- Top Bar --}}
        <div class="wf-topbar">
            <div class="wf-topbar-left">
                <a href="{{ \Relaticle\Workflow\Filament\Resources\WorkflowResource::getUrl() }}" class="wf-back-link" title="Back to workflows">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <div class="wf-name-wrapper">
                    <template x-if="!editingName">
                        <h1 class="wf-name" x-text="workflowName" @click="editingName = true; $nextTick(() => $refs.nameInput.focus())"></h1>
                    </template>
                    <template x-if="editingName">
                        <input
                            x-ref="nameInput"
                            type="text"
                            x-model="workflowName"
                            @blur="editingName = false; saveName()"
                            @keydown.enter="editingName = false; saveName()"
                            @keydown.escape="editingName = false"
                            class="wf-name-input"
                        >
                    </template>
                </div>
                <span class="wf-status-badge" :class="'wf-status-' + workflowStatus" x-text="workflowStatus"></span>
            </div>
            <div class="wf-topbar-right">
                <button
                    type="button"
                    class="wf-topbar-btn"
                    @click="togglePanel('runs')"
                    :class="{ 'active': panelView === 'runs' }"
                    title="Run History"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Runs</span>
                </button>
                <button
                    type="button"
                    class="wf-topbar-btn"
                    @click="togglePanel('settings')"
                    :class="{ 'active': panelView === 'settings' }"
                    title="Settings"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Settings</span>
                </button>
                <div class="wf-topbar-divider"></div>
                <button
                    type="button"
                    class="wf-save-btn"
                    @click="saveCanvas()"
                    :disabled="saving"
                    id="save-btn"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span x-text="saving ? 'Saving...' : 'Save'"></span>
                </button>
                <button
                    type="button"
                    class="wf-publish-btn"
                    @click="publishWorkflow()"
                    :disabled="publishing"
                    x-show="workflowStatus !== 'archived'"
                >
                    <template x-if="workflowStatus === 'live'">
                        <span>Publish Changes</span>
                    </template>
                    <template x-if="workflowStatus !== 'live'">
                        <span>Publish</span>
                    </template>
                </button>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="wf-content">
            {{-- Canvas --}}
            <div class="wf-canvas-area">
                <div id="workflow-canvas-container">
                    <div id="workflow-canvas"></div>
                </div>

                {{-- Bottom Toolbar --}}
                <div class="wf-toolbar-bottom">
                    <div class="wf-toolbar-group">
                        <button type="button" class="wf-tool-btn" :class="{ 'active': interactionMode === 'pointer' }" @click="setMode('pointer')" title="Select (V)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/></svg>
                        </button>
                        <button type="button" class="wf-tool-btn" :class="{ 'active': interactionMode === 'hand' }" @click="setMode('hand')" title="Pan (H)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"/><path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2"/><path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"/><path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 13"/></svg>
                        </button>
                    </div>
                    <div class="wf-toolbar-divider"></div>
                    <div class="wf-toolbar-group">
                        <button type="button" class="wf-tool-btn" @click="zoomOut()" title="Zoom Out">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </button>
                        <span class="wf-zoom-level" x-text="zoomLabel"></span>
                        <button type="button" class="wf-tool-btn" @click="zoomIn()" title="Zoom In">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </button>
                        <button type="button" class="wf-tool-btn" @click="fitToView()" title="Fit to View">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                        </button>
                    </div>
                    <div class="wf-toolbar-divider"></div>
                    <div class="wf-toolbar-group">
                        <button type="button" class="wf-tool-btn" @click="organizeBlocks()" title="Auto-organize">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        </button>
                        <button type="button" class="wf-tool-btn" @click="undoAction()" title="Undo (Ctrl+Z)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                        </button>
                        <button type="button" class="wf-tool-btn" @click="redoAction()" title="Redo (Ctrl+Shift+Z)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 14 20 9 15 4"/><path d="M4 20v-7a4 4 0 0 1 4-4h12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Minimap --}}
                <div id="workflow-minimap" class="wf-minimap"></div>
            </div>

            {{-- Right Panel --}}
            <div class="wf-panel" :class="{ 'wf-panel-open': panelOpen }">
                {{-- Config Panel (shown when a node is selected) --}}
                <div x-show="panelView === 'config' && selectedNode" class="wf-panel-content">
                    <div class="wf-panel-header">
                        <h3 x-text="selectedNode?.type ? selectedNode.type.charAt(0).toUpperCase() + selectedNode.type.slice(1) + ' Settings' : 'Settings'"></h3>
                        <button type="button" @click="deselectNode()" class="wf-panel-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <div id="config-panel-body" class="wf-panel-body">
                        {{-- Populated dynamically by config-panel.js --}}
                    </div>
                </div>

                {{-- Settings Panel --}}
                <div x-show="panelView === 'settings'" class="wf-panel-content">
                    <div class="wf-panel-header">
                        <h3>Workflow Settings</h3>
                        <button type="button" @click="closePanel()" class="wf-panel-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <div class="wf-panel-body">
                        <p class="wf-panel-placeholder">Workflow settings coming soon.</p>
                    </div>
                </div>

                {{-- Runs Panel --}}
                <div x-show="panelView === 'runs'" class="wf-panel-content" x-data="runHistory('{{ $workflowId }}')">
                    <div class="wf-panel-header">
                        <h3>Run History</h3>
                        <button type="button" @click="$dispatch('close-panel')" class="wf-panel-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <div class="wf-panel-body">
                        <template x-if="!runViewActive">
                            <div>
                                <button type="button" class="wf-btn-sm" @click="loadRuns()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                    Refresh
                                </button>
                                <div class="wf-runs-list">
                                    <template x-for="run in runs" :key="run.id">
                                        <div class="wf-run-item" @click="selectRun(run.id)">
                                            <span class="wf-run-status" :class="'wf-run-' + run.status"></span>
                                            <span class="wf-run-time" x-text="formatTime(run.started_at)"></span>
                                            <span class="wf-run-status-text" x-text="run.status"></span>
                                        </div>
                                    </template>
                                    <template x-if="runs.length === 0">
                                        <p class="wf-panel-placeholder">No runs yet.</p>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <template x-if="runViewActive && selectedRun">
                            <div>
                                <button type="button" class="wf-btn-sm" @click="exitRunView()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                    Back to runs
                                </button>
                                <div class="wf-run-detail">
                                    <div class="wf-run-detail-header">
                                        <span class="wf-run-status" :class="'wf-run-' + selectedRun.status"></span>
                                        <strong x-text="selectedRun.status"></strong>
                                        <span x-text="formatTime(selectedRun.started_at)"></span>
                                    </div>
                                    <template x-if="selectedRun.error_message">
                                        <div class="wf-run-error" x-text="selectedRun.error_message"></div>
                                    </template>
                                    <div class="wf-run-steps">
                                        <h4>Steps</h4>
                                        <template x-for="step in selectedRun.steps" :key="step.id">
                                            <div class="wf-run-step" :class="'wf-step-' + step.status">
                                                <span class="wf-run-status" :class="'wf-run-' + step.status"></span>
                                                <span x-text="step.node_id || 'Unknown'"></span>
                                                <span class="wf-step-status" x-text="step.status"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Block Picker Popover --}}
        <div
            x-show="blockPickerOpen"
            x-transition
            class="wf-picker"
            :style="'left:' + blockPickerPos.x + 'px; top:' + blockPickerPos.y + 'px'"
            @click.outside="blockPickerOpen = false"
        >
            <div class="wf-picker-search">
                <input
                    type="text"
                    x-model="blockPickerSearch"
                    placeholder="Search blocks..."
                    class="wf-picker-input"
                    x-ref="pickerSearchInput"
                >
            </div>
            <div class="wf-picker-categories">
                <template x-for="category in filteredCategories" :key="category.name">
                    <div class="wf-picker-category">
                        <div class="wf-picker-category-name" x-text="category.name"></div>
                        <template x-for="block in category.blocks" :key="block.type + (block.actionType || '')">
                            <button
                                type="button"
                                class="wf-picker-item"
                                @click="addBlock(block)"
                            >
                                <span class="wf-picker-icon" :style="'color:' + block.color" x-html="block.icon"></span>
                                <span x-text="block.label"></span>
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- Toast Container --}}
        <div id="toast-container" class="wf-toast-container"></div>

        {{-- Confirmation Dialog --}}
        <div id="confirm-dialog" class="wf-confirm-overlay" style="display: none;">
            <div class="wf-confirm-dialog">
                <p id="confirm-message"></p>
                <div class="wf-confirm-actions">
                    <button type="button" id="confirm-cancel" class="wf-btn-secondary">Cancel</button>
                    <button type="button" id="confirm-ok" class="wf-btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('vendor/workflow/workflow-builder.js') }}" defer></script>
    @endpush
</x-filament-panels::page>
