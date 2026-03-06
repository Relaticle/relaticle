<x-filament-panels::page>
    @assets
        <link rel="stylesheet" href="{{ asset('vendor/workflow/workflow-builder.css') }}">
        <script src="{{ asset('vendor/workflow/workflow-builder.js') }}"></script>
    @endassets

    <div
        x-data="workflowBuilder('{{ $workflowId }}', '{{ $workflowStatus ?? 'draft' }}', {{ json_encode($workflowName ?? '') }})"
        class="workflow-builder"
    >
        {{-- Top Bar --}}
        <div class="wf-topbar">
            <div class="wf-topbar-left">
                <a href="{{ \Relaticle\Workflow\Filament\Resources\WorkflowResource::getUrl() }}" class="wf-breadcrumb-link" title="Back to workflows">
                    <span class="wf-breadcrumb-text">Workflows</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
                <div class="wf-name-wrapper">
                    <template x-if="!editingName">
                        <h1 class="wf-name" x-text="workflowName" :title="workflowName" @click="editingName = true; $nextTick(() => $refs.nameInput.focus())"></h1>
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
                <div class="flex items-center h-full gap-0">
                    <button
                        type="button"
                        class="px-3.5 h-full text-[13px] font-medium bg-transparent border-0 border-b-2 border-solid cursor-pointer transition-all whitespace-nowrap flex items-center gap-1.5"
                        :class="panelView !== 'runs' && panelView !== 'settings'
                            ? 'text-blue-500 dark:text-blue-400 border-b-blue-500 dark:border-b-blue-400'
                            : 'text-slate-500 dark:text-slate-400 border-b-transparent hover:text-slate-700 dark:hover:text-slate-200'"
                        @click="closePanel()"
                    ><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>Editor</button>
                    <button
                        type="button"
                        class="px-3.5 h-full text-[13px] font-medium bg-transparent border-0 border-b-2 border-solid cursor-pointer transition-all whitespace-nowrap flex items-center gap-1.5"
                        :class="panelView === 'runs'
                            ? 'text-blue-500 dark:text-blue-400 border-b-blue-500 dark:border-b-blue-400'
                            : 'text-slate-500 dark:text-slate-400 border-b-transparent hover:text-slate-700 dark:hover:text-slate-200'"
                        @click="togglePanel('runs')"
                    ><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="6 3 20 12 6 21 6 3"/></svg>Runs <span
                        x-show="totalRunCount > 0"
                        x-text="totalRunCount"
                        class="ml-0.5 px-1.5 py-0.5 text-[10px] font-semibold bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-full"
                    ></span></button>
                    <button
                        type="button"
                        class="px-3.5 h-full text-[13px] font-medium bg-transparent border-0 border-b-2 border-solid cursor-pointer transition-all whitespace-nowrap flex items-center gap-1.5"
                        :class="panelView === 'settings'
                            ? 'text-blue-500 dark:text-blue-400 border-b-blue-500 dark:border-b-blue-400'
                            : 'text-slate-500 dark:text-slate-400 border-b-transparent hover:text-slate-700 dark:hover:text-slate-200'"
                        @click="togglePanel('settings')"
                    ><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>Settings</button>
                </div>
                <div class="wf-topbar-divider"></div>
                <button
                    type="button"
                    class="wf-save-btn relative"
                    @click="saveCanvas()"
                    :disabled="saving"
                    :class="{ 'border-amber-400!': isDirty }"
                    id="save-btn"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span x-text="saving ? 'Saving...' : 'Save'"></span>
                    <span x-show="isDirty" class="absolute top-1 right-1 w-1.5 h-1.5 bg-amber-400 rounded-full"></span>
                </button>
                <button
                    type="button"
                    class="wf-topbar-btn"
                    @click="runTestRun()"
                    :disabled="testRunning"
                    title="Test this workflow without side effects"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="6 3 20 12 6 21 6 3"/></svg>
                    <span x-text="testRunning ? 'Testing...' : 'Test Run'"></span>
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
                <button
                    type="button"
                    class="wf-topbar-btn"
                    @click="pauseWorkflow()"
                    x-show="workflowStatus === 'live'"
                    title="Pause Workflow"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                    <span>Pause</span>
                </button>
                <button
                    type="button"
                    class="wf-topbar-btn"
                    @click="archiveWorkflow()"
                    x-show="workflowStatus === 'live' || workflowStatus === 'paused'"
                    title="Archive Workflow"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                    <span>Archive</span>
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

                {{-- Empty Canvas Onboarding --}}
                <div class="absolute inset-0 flex flex-col items-center justify-center z-10 pointer-events-none" x-show="!hasNodes" x-transition>
                    <div class="text-slate-400 dark:text-slate-600 mb-4 pointer-events-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    </div>
                    <h2 class="text-lg font-semibold text-slate-700 dark:text-slate-200 m-0 mb-2 pointer-events-auto">Start building your workflow</h2>
                    <p class="text-sm text-slate-400 m-0 mb-5 pointer-events-auto">Double-click the canvas or use the + button to add your first block.</p>
                    <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-blue-500 hover:bg-blue-600 border-none rounded-lg cursor-pointer transition-colors pointer-events-auto" @click="openBlockPicker()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Trigger
                    </button>
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
                        <button type="button" class="wf-tool-btn" @click="openBlockPicker()" title="Add Block">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </button>
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
            <div class="wf-panel" :class="{ 'wf-panel-open': panelOpen || blockPickerOpen }">
                {{-- Config Panel (shown when a node is selected) --}}
                <div
                    x-show="panelView === 'config'"
                    x-cloak
                    x-on:config-panel-close.window="deselectNode()"
                    class="wf-panel-content"
                >
                    @livewire('workflow-config-panel', ['workflowId' => $workflowId])
                </div>

                {{-- Settings Panel --}}
                <div x-show="panelView === 'settings'" x-cloak class="wf-panel-content">
                    <div class="wf-panel-header">
                        <h3>Workflow Settings</h3>
                        <button type="button" @click="closePanel()" class="wf-panel-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <div class="wf-panel-body">
                        <div class="mb-6">
                            <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide m-0 mb-2.5">Description</h4>
                            <textarea
                                class="w-full p-2 text-[13px] text-slate-800 dark:text-slate-200 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md outline-none resize-y font-[inherit] focus:border-blue-500"
                                x-model="workflowDescription"
                                @blur="saveDescription()"
                                placeholder="Add a workflow description..."
                                rows="3"
                            ></textarea>
                        </div>

                        <div class="mb-6">
                            <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide m-0 mb-2.5">Trigger</h4>
                            <div class="flex justify-between py-2 text-[13px] border-b border-slate-100 dark:border-slate-700">
                                <span class="text-slate-500">Type</span>
                                <span class="text-slate-800 dark:text-slate-200 font-medium" x-text="triggerType || 'Not configured'"></span>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide m-0 mb-2.5">Execution Limits</h4>
                            <div class="flex items-center justify-between py-2">
                                <label class="text-[13px] text-slate-700 dark:text-slate-200">Maximum steps per run</label>
                                <input
                                    type="number"
                                    x-model="maxStepsPerRun"
                                    min="1"
                                    max="1000"
                                    class="w-20 text-[13px] text-slate-800 dark:text-slate-200 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 outline-none focus:border-blue-500"
                                    @blur="saveSettings()"
                                >
                            </div>
                            <p class="text-[11px] text-slate-400 mt-0.5">Workflow run will stop if this limit is reached.</p>
                        </div>

                        <div class="mb-6">
                            <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide m-0 mb-2.5">Notifications</h4>
                            <div class="flex items-center justify-between py-2">
                                <div>
                                    <label class="text-[13px] font-medium text-slate-700 dark:text-slate-200">Notify on failure</label>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Receive a notification when this workflow fails</p>
                                </div>
                                <button
                                    type="button"
                                    @click="notifyOnFailure = !notifyOnFailure; saveSettings()"
                                    :class="notifyOnFailure ? 'bg-blue-500' : 'bg-slate-300 dark:bg-slate-600'"
                                    class="relative w-10 h-5 rounded-full transition-colors flex-shrink-0 border-0 cursor-pointer"
                                >
                                    <span
                                        :class="notifyOnFailure ? 'translate-x-5' : 'translate-x-0.5'"
                                        class="block w-4 h-4 bg-white rounded-full transition-transform shadow absolute top-0.5"
                                    ></span>
                                </button>
                            </div>
                        </div>

                        <div class="border-t border-red-100 dark:border-red-900/30 pt-4">
                            <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide m-0 mb-2.5">Danger Zone</h4>
                            <button
                                type="button"
                                class="wf-btn-sm text-red-500 border-red-200 hover:bg-red-50 hover:text-red-600"
                                @click="archiveWorkflow()"
                                x-show="workflowStatus === 'live' || workflowStatus === 'paused'"
                            >Archive Workflow</button>
                        </div>
                    </div>
                </div>

                {{-- Block Picker Panel (shown when + is clicked) --}}
                <div x-show="blockPickerOpen" x-cloak class="wf-panel-content">
                    <div class="wf-panel-header">
                        <div>
                            <h3 class="m-0">Next step</h3>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 m-0 mt-0.5">Set the next block in the workflow</p>
                        </div>
                        <button type="button" @click="blockPickerOpen = false" class="wf-panel-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700">
                        <input
                            type="text"
                            x-model="blockPickerSearch"
                            placeholder="Search blocks..."
                            class="wf-picker-input"
                            x-ref="pickerSearchInput"
                            @keydown="pickerKeydown($event)"
                        >
                    </div>
                    <div class="wf-panel-body" style="padding: 6px;">
                        <template x-for="category in filteredCategories" :key="category.name">
                            <div class="wf-picker-category">
                                <div class="wf-picker-category-name" x-text="category.name"></div>
                                <template x-for="block in category.blocks" :key="block.type + (block.actionType || '')">
                                    <button
                                        type="button"
                                        :class="['wf-picker-item', block.type + (block.actionType || '') === blockPickerHighlightKey ? 'wf-picker-item-hl' : '']"
                                        @click="addBlock(block)"
                                        @mouseenter="blockPickerHighlightKey = block.type + (block.actionType || '')"
                                    >
                                        <span class="wf-picker-icon" :style="'background:' + block.color + '; color: #fff'" x-html="block.icon"></span>
                                        <span class="flex flex-col min-w-0">
                                            <span class="text-[13px] font-medium leading-snug" x-text="block.label"></span>
                                            <span class="text-[11px] text-slate-400 dark:text-slate-500 truncate" x-text="block.description"></span>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Runs Panel --}}
                <div x-show="panelView === 'runs'" x-cloak class="wf-panel-content" x-data="runHistory('{{ $workflowId }}')">
                    <div class="wf-panel-header">
                        <h3>Run History</h3>
                        <button type="button" @click="panelOpen = false; panelView = null; $dispatch('close-panel')" class="wf-panel-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <div class="wf-panel-body">
                        <template x-if="!runViewActive">
                            <div>
                                {{-- Status Summary --}}
                                <div class="flex items-center gap-2 mb-3">
                                    <template x-if="completedCount > 0">
                                        <span class="px-2 py-0.5 text-[11px] font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                                            Completed <span x-text="completedCount"></span>
                                        </span>
                                    </template>
                                    <template x-if="failedCount > 0">
                                        <span class="px-2 py-0.5 text-[11px] font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">
                                            Failed <span x-text="failedCount"></span>
                                        </span>
                                    </template>
                                    <template x-if="runningCount > 0">
                                        <span class="px-2 py-0.5 text-[11px] font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                            Running <span x-text="runningCount"></span>
                                        </span>
                                    </template>
                                    <button type="button" class="ml-auto text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-1 rounded transition-colors" @click="loadRuns()" title="Refresh">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                    </button>
                                </div>

                                {{-- Loading state --}}
                                <template x-if="loadingRuns">
                                    <div class="flex flex-col items-center gap-3 py-8 text-slate-400 text-[13px]">
                                        <div class="wf-spinner"></div>
                                        <span>Loading runs...</span>
                                    </div>
                                </template>
                                <div class="space-y-0.5" x-show="!loadingRuns">
                                    <template x-for="run in runs" :key="run.id">
                                        <button
                                            type="button"
                                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2.5 transition-colors cursor-pointer border-0 bg-transparent"
                                            @click="selectRun(run.id)"
                                        >
                                            <span class="w-2 h-2 rounded-full flex-shrink-0" :class="getStatusColor(run.status)"></span>
                                            <span class="text-[13px] font-medium text-slate-700 dark:text-slate-200" x-text="'Run #' + run.number"></span>
                                            <span class="text-xs text-slate-400 ml-auto flex-shrink-0" x-text="formatTime(run.started_at)"></span>
                                            <template x-if="run.completed_at && run.started_at">
                                                <span class="text-[10px] text-slate-400 flex-shrink-0" x-text="formatDuration(run.started_at, run.completed_at)"></span>
                                            </template>
                                        </button>
                                    </template>
                                    <template x-if="runs.length === 0 && !loadingRuns">
                                        <div class="flex flex-col items-center text-center py-8 px-4 text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-slate-300 dark:text-slate-600"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 m-0 mb-1">No runs yet</p>
                                            <span class="text-xs text-slate-400">Publish and trigger your workflow to see results here.</span>
                                        </div>
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
                                <div class="mt-3">
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="w-2.5 h-2.5 rounded-full" :class="getStatusColor(selectedRun.status)"></span>
                                        <strong class="text-sm text-slate-700 dark:text-slate-200 capitalize" x-text="selectedRun.status"></strong>
                                        <span class="text-xs text-slate-400" x-text="formatTime(selectedRun.started_at)"></span>
                                        <template x-if="selectedRun.completed_at && selectedRun.started_at">
                                            <span class="text-xs text-slate-400 ml-auto" x-text="formatDuration(selectedRun.started_at, selectedRun.completed_at)"></span>
                                        </template>
                                    </div>
                                    <template x-if="selectedRun.error_message">
                                        <div class="px-3 py-2 mb-3 text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg" x-text="selectedRun.error_message"></div>
                                    </template>
                                    <div>
                                        <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide m-0 mb-2">Steps</h4>
                                        <div class="space-y-1">
                                            <template x-for="step in selectedRun.steps" :key="step.id">
                                                <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[13px]"
                                                     :class="{
                                                         'bg-green-50 dark:bg-green-900/10': step.status === 'completed',
                                                         'bg-red-50 dark:bg-red-900/10': step.status === 'failed',
                                                         'bg-slate-50 dark:bg-slate-800': step.status === 'skipped' || step.status === 'pending',
                                                     }">
                                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="getStatusColor(step.status)"></span>
                                                    <span class="text-slate-700 dark:text-slate-200 truncate" x-text="getNodeLabel(step.node_id)"></span>
                                                    <span class="text-[11px] text-slate-400 ml-auto capitalize flex-shrink-0" x-text="step.status"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Block Picker Popover is now integrated into the right panel --}}

        {{-- Edge Add Button --}}
        <button
            type="button"
            class="fixed w-6 h-6 flex items-center justify-center bg-white dark:bg-slate-800 border-[1.5px] border-blue-500 dark:border-blue-400 rounded-full text-blue-500 dark:text-blue-400 cursor-pointer z-50 -translate-x-1/2 -translate-y-1/2 shadow-md transition-all hover:bg-blue-500 hover:text-white hover:scale-110"
            x-show="edgeAddBtn.visible"
            :style="'left:' + edgeAddBtn.x + 'px; top:' + edgeAddBtn.y + 'px'"
            @mouseenter="_edgeAddHover = true"
            @mouseleave="_edgeAddHover = false; edgeAddBtn.visible = false"
            @click="_insertOnEdge = edgeAddBtn.edgeId; openBlockPicker()"
            title="Insert block"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </button>

        {{-- Variable Picker Popover --}}
        <div
            x-show="varPickerOpen"
            x-transition
            class="wf-var-picker"
            :style="'left:' + varPickerPos.x + 'px; top:' + varPickerPos.y + 'px'"
            @click.outside="closeVariablePicker()"
        >
            <template x-for="group in variables" :key="group.nodeId">
                <div>
                    <div class="wf-var-group-name" x-text="group.source"></div>
                    <template x-for="output in group.outputs" :key="output.key">
                        <button
                            type="button"
                            class="wf-var-item"
                            @click="insertVariable(group.nodeId, output.key)"
                        >
                            <span class="wf-var-key" x-text="output.key"></span>
                            <span class="wf-var-label" x-text="output.label"></span>
                        </button>
                    </template>
                </div>
            </template>
            <template x-if="variables.length === 0">
                <p class="wf-panel-placeholder">No upstream variables available.</p>
            </template>
        </div>

        {{-- Step Detail Popover (Run View) --}}
        <div
            x-show="stepPopover?.visible"
            x-cloak
            x-transition
            :style="`position:fixed; left:${stepPopover?.x}px; top:${stepPopover?.y}px; z-index:200;`"
            @click.outside="stepPopover = null"
            class="w-80 bg-gray-900 text-white rounded-xl shadow-2xl p-4 text-sm"
        >
            <div class="flex items-center gap-2 mb-3">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                      :class="{
                          'bg-green-500/20 text-green-300': stepPopover?.step?.status === 'completed',
                          'bg-red-500/20 text-red-300': stepPopover?.step?.status === 'failed',
                          'bg-blue-500/20 text-blue-300': stepPopover?.step?.status === 'running',
                          'bg-gray-500/20 text-gray-300': stepPopover?.step?.status === 'skipped' || stepPopover?.step?.status === 'pending',
                      }"
                      x-text="stepPopover?.step?.status"></span>
                <span class="text-gray-400 text-xs ml-auto"
                      x-show="stepPopover?.step?.started_at && stepPopover?.step?.completed_at"
                      x-text="(() => {
                          const s = stepPopover?.step;
                          if (!s?.started_at || !s?.completed_at) return '';
                          const ms = new Date(s.completed_at) - new Date(s.started_at);
                          if (ms < 1000) return ms + 'ms';
                          if (ms < 60000) return Math.round(ms / 1000) + 's';
                          return Math.round(ms / 60000) + 'm';
                      })()"></span>
            </div>

            <div class="space-y-1 text-xs text-gray-400 mb-3">
                <div x-show="stepPopover?.step?.started_at">Started: <span class="text-gray-200" x-text="stepPopover?.step?.started_at ? new Date(stepPopover.step.started_at).toLocaleTimeString() : ''"></span></div>
                <div x-show="stepPopover?.step?.completed_at">Completed: <span class="text-gray-200" x-text="stepPopover?.step?.completed_at ? new Date(stepPopover.step.completed_at).toLocaleTimeString() : ''"></span></div>
            </div>

            <div x-show="stepPopover?.step?.input_data && Object.keys(stepPopover?.step?.input_data || {}).length > 0" class="mb-3">
                <h5 class="text-xs font-semibold text-gray-400 uppercase mb-1">Inputs</h5>
                <pre class="text-xs bg-gray-800 rounded p-2 max-h-32 overflow-auto whitespace-pre-wrap break-all"
                     x-text="JSON.stringify(stepPopover?.step?.input_data, null, 2)"></pre>
            </div>

            <div x-show="stepPopover?.step?.output_data && Object.keys(stepPopover?.step?.output_data || {}).length > 0" class="mb-3">
                <h5 class="text-xs font-semibold text-gray-400 uppercase mb-1">Outputs</h5>
                <pre class="text-xs bg-gray-800 rounded p-2 max-h-32 overflow-auto whitespace-pre-wrap break-all"
                     x-text="JSON.stringify(stepPopover?.step?.output_data, null, 2)"></pre>
            </div>

            <div x-show="stepPopover?.step?.error_message" class="text-red-400 text-xs">
                <h5 class="font-semibold uppercase mb-1">Error</h5>
                <p x-text="stepPopover?.step?.error_message" class="m-0"></p>
            </div>
        </div>

        {{-- Test Run Results Modal --}}
        {{-- Test Run Slideover --}}
        <div x-show="testRunResults" x-cloak class="fixed inset-0 z-[9999] flex" @click.self="testRunResults = null">
            <div class="absolute inset-0 bg-black/20" @click="testRunResults = null"></div>
            <div class="ml-auto relative w-full max-w-md h-full bg-white dark:bg-slate-800 shadow-2xl flex flex-col"
                 x-show="testRunResults"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 @click.stop>

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-700 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><polygon points="6 3 20 12 6 21 6 3"/></svg>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white m-0">Test Run</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                            :class="testRunResults?.status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                            x-text="testRunResults?.status === 'completed' ? 'Passed' : 'Failed'"></span>
                    </div>
                    <button type="button" @click="testRunResults = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 bg-transparent border-0 cursor-pointer p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                {{-- Summary bar --}}
                <div class="px-5 py-2.5 bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700 shrink-0">
                    <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            Executed: <span class="font-medium text-slate-700 dark:text-slate-300" x-text="(testRunResults?.steps || []).filter(s => s.status === 'completed' && !s.dry_run_skipped).length"></span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            Simulated: <span class="font-medium text-slate-700 dark:text-slate-300" x-text="(testRunResults?.steps || []).filter(s => s.dry_run_skipped).length"></span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            Failed: <span class="font-medium text-slate-700 dark:text-slate-300" x-text="(testRunResults?.steps || []).filter(s => s.status === 'failed').length"></span>
                        </span>
                    </div>
                </div>

                {{-- Info banner --}}
                <div class="px-5 py-2 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800/30 shrink-0">
                    <p class="text-xs text-blue-600 dark:text-blue-400 m-0">
                        <strong>Test mode:</strong> Actions that modify data (emails, record updates, webhooks) are simulated — no real changes were made.
                    </p>
                </div>

                {{-- Global error --}}
                <template x-if="testRunResults?.error && !testRunResults?.steps?.length">
                    <div class="px-5 py-3 shrink-0">
                        <div class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg p-3" x-text="testRunResults.error"></div>
                    </div>
                </template>

                {{-- Steps --}}
                <div class="overflow-y-auto flex-1 px-5 py-3">
                    <template x-for="(step, idx) in (testRunResults?.steps || [])" :key="idx">
                        <div class="mb-2.5">
                            <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden"
                                 :class="{ 'border-red-300 dark:border-red-700': step.status === 'failed' }">

                                {{-- Step header --}}
                                <button type="button"
                                    class="w-full flex items-center gap-2.5 px-3 py-2.5 bg-transparent border-0 cursor-pointer text-left hover:bg-slate-50 dark:hover:bg-slate-700/50"
                                    @click="step._expanded = !step._expanded">

                                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0"
                                        :class="{
                                            'bg-green-500': step.status === 'completed' && !step.dry_run_skipped,
                                            'bg-amber-400': step.dry_run_skipped,
                                            'bg-red-500': step.status === 'failed',
                                            'bg-gray-400': step.status === 'skipped',
                                        }" x-text="idx + 1"></span>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate" x-text="step.action_label"></span>
                                            <template x-if="step.dry_run_skipped">
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 font-medium shrink-0">Simulated</span>
                                            </template>
                                            <template x-if="step.status === 'failed'">
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 font-medium shrink-0">Error</span>
                                            </template>
                                        </div>
                                        <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5" x-text="step.node_type + (step.action_type ? ' → ' + step.action_type : '')"></div>
                                    </div>

                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         class="text-slate-400 shrink-0 transition-transform duration-150"
                                         :class="{ 'rotate-180': step._expanded }">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </button>

                                {{-- Expanded details --}}
                                <div x-show="step._expanded" x-collapse class="border-t border-slate-100 dark:border-slate-700">
                                    {{-- Input data --}}
                                    <template x-if="step.input && Object.keys(step.input).length">
                                        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700/50">
                                            <div class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Input</div>
                                            <pre class="text-xs bg-slate-50 dark:bg-slate-900 rounded p-2 overflow-x-auto m-0 text-slate-600 dark:text-slate-400 max-h-32 overflow-y-auto" x-text="JSON.stringify(step.input, null, 2)"></pre>
                                        </div>
                                    </template>

                                    {{-- Output data --}}
                                    <template x-if="step.output && Object.keys(step.output).length">
                                        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700/50">
                                            <div class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">
                                                <span x-text="step.dry_run_skipped ? 'Would produce' : 'Output'"></span>
                                            </div>
                                            <pre class="text-xs bg-slate-50 dark:bg-slate-900 rounded p-2 overflow-x-auto m-0 text-slate-600 dark:text-slate-400 max-h-32 overflow-y-auto" x-text="JSON.stringify(Object.fromEntries(Object.entries(step.output).filter(([k]) => !k.startsWith('_'))), null, 2)"></pre>
                                        </div>
                                    </template>

                                    {{-- Error --}}
                                    <template x-if="step.error">
                                        <div class="px-3 py-2">
                                            <div class="text-[10px] font-semibold text-red-400 uppercase tracking-wider mb-1">Error</div>
                                            <div class="text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded p-2" x-text="step.error"></div>
                                        </div>
                                    </template>

                                    {{-- Simulated explanation --}}
                                    <template x-if="step.dry_run_skipped && !step.error">
                                        <div class="px-3 py-2">
                                            <div class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/10 rounded p-2">
                                                This action was simulated — in a real run, it would execute and modify data.
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
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


</x-filament-panels::page>
