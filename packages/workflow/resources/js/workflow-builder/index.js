/**
 * Workflow Builder — Main Entry Point
 *
 * Registers Alpine.js components, initializes the X6 graph,
 * and sets up the bidirectional event bridge between Alpine and X6.
 */

import '../../css/workflow-builder.css';
import { createGraph, enterRunView, exitRunView, ensureEdgesBehindNodes } from './graph.js';
import { registerTriggerNode } from './nodes/TriggerNode.js';
import { registerActionNode } from './nodes/ActionNode.js';
import { registerConditionNode } from './nodes/ConditionNode.js';
import { registerDelayNode } from './nodes/DelayNode.js';
import { registerLoopNode } from './nodes/LoopNode.js';
import { registerStopNode } from './nodes/StopNode.js';
import { registerFilterNode } from './nodes/FilterNode.js';
import { registerSwitchNode } from './nodes/SwitchNode.js';
import { validateAllNodes, clearValidationErrors } from './config-panel.js';
import { organizeLayout } from './toolbar.js';
import { configPanelComponent } from './alpine/config-panel.js';
import { blockPickerData, addBlockToGraph, getContextualDescription } from './alpine/block-picker.js';
import { variablePickerComponent } from './alpine/variable-picker.js';
import { topBarMixin } from './alpine/top-bar.js';
import { runHistoryComponent } from './alpine/run-history.js';

// ── Toast Notifications ──────────────────────────────────────────────

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `wf-toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ── Shape Map ────────────────────────────────────────────────────────

const SHAPE_MAP = {
    trigger: 'workflow-trigger',
    action: 'workflow-action',
    condition: 'workflow-condition',
    filter: 'workflow-filter',
    switch: 'workflow-switch',
    delay: 'workflow-delay',
    loop: 'workflow-loop',
    stop: 'workflow-stop',
};

// ── Alpine Registration ──────────────────────────────────────────────

function workflowBuilderFactory(workflowId, initialStatus, initialName) {
    const topBar = topBarMixin();
    const blockPicker = blockPickerData();
    const configPanel = configPanelComponent();
    const varPicker = variablePickerComponent();

    return {
        // Core state
        workflowId,
        workflowName: initialName || 'Untitled Workflow',
        workflowStatus: initialStatus || 'draft',

        // Panel state
        panelView: null,  // 'config' | 'settings' | 'runs'
        panelOpen: false,
        selectedNode: null,

        // Canvas state
        saving: false,
        interactionMode: 'pointer',
        zoomLevel: 1,
        hasNodes: false,
        isDirty: false,
        edgeAddBtn: { visible: false, x: 0, y: 0, edgeId: null },
        _edgeAddHover: false,
        _insertOnEdge: null,

        // Run view popover state
        stepPopover: null,
        totalRunCount: 0,

        // Test run state
        testRunning: false,
        testRunResults: null,

        // Block picker state (sidebar)
        blockPickerOpen: false,
        blockPickerSearch: '',
        blockPickerSourceNode: null,
        blockPickerHighlightKey: '',

        // Add-step placeholder
        addStepPlaceholder: { visible: false, x: 0, y: 0, sourceNodeId: null },

        // Spread mixins
        ...topBar,
        ...blockPicker,
        ...configPanel,
        ...varPicker,

        get zoomLabel() {
            return Math.round(this.zoomLevel * 100) + '%';
        },

        get filteredCategories() {
            const graph = window.__wfGraph;
            const manifest = window.__wfManifest || {};
            const blockRules = manifest.blocks || {};
            const query = this.blockPickerSearch.toLowerCase().trim();

            // Check if trigger exists on graph
            const hasTrigger = graph
                ? graph.getNodes().some(n => (n.getData() || {}).type === 'trigger')
                : false;

            // Determine allowed target types based on source node
            let allowedTargets = null;
            if (this.blockPickerSourceNode && graph) {
                const sourceCell = graph.getCellById(this.blockPickerSourceNode);
                if (sourceCell) {
                    const sourceData = sourceCell.getData() || {};
                    const sourceType = sourceData.type;
                    const rules = blockRules[sourceType];
                    if (rules) {
                        allowedTargets = [...rules.allowedTargets];

                        // Also check if source already has max outgoing
                        if (rules.maxOutgoing !== null && rules.maxOutgoing !== undefined) {
                            const existingOut = graph.getConnectedEdges(sourceCell, { outgoing: true });
                            if (existingOut.length >= rules.maxOutgoing) {
                                allowedTargets = []; // No more connections allowed
                            }
                        }
                    }
                }
            }

            // Resolve source node for contextual descriptions
            let sourceNodeForContext = null;
            if (this.blockPickerSourceNode && graph) {
                sourceNodeForContext = graph.getCellById(this.blockPickerSourceNode);
                // Walk upstream to find trigger context if source isn't trigger itself
                if (sourceNodeForContext && sourceNodeForContext.getData()?.type !== 'trigger') {
                    const trigNode = graph.getNodes().find(n => (n.getData() || {}).type === 'trigger');
                    if (trigNode) sourceNodeForContext = trigNode;
                }
            }

            return this.categories
                .map(cat => {
                    const filteredBlocks = cat.blocks.filter(block => {
                        // Hide trigger if one exists
                        if (block.type === 'trigger' && hasTrigger) return false;

                        // Filter by search query
                        if (query && !block.label.toLowerCase().includes(query)
                            && !block.description.toLowerCase().includes(query)) {
                            return false;
                        }

                        // Filter by connection rules if adding from a specific source
                        if (allowedTargets !== null) {
                            if (!allowedTargets.includes(block.type)) {
                                return false;
                            }
                        }

                        return true;
                    }).map(block => {
                        if (!sourceNodeForContext) return block;
                        return { ...block, description: getContextualDescription(block, sourceNodeForContext) };
                    });

                    return { ...cat, blocks: filteredBlocks };
                })
                .filter(cat => cat.blocks.length > 0);
        },

        get flatPickerBlocks() {
            return this.filteredCategories.flatMap(cat => cat.blocks);
        },

        init() {
            // Register X6 node shapes
            registerTriggerNode();
            registerActionNode();
            registerConditionNode();
            registerDelayNode();
            registerLoopNode();
            registerStopNode();
            registerFilterNode();
            registerSwitchNode();

            // Create graph
            const container = document.getElementById('workflow-canvas');
            const minimapContainer = document.getElementById('workflow-minimap');
            if (!container) return;

            const graph = createGraph(container, minimapContainer);
            window.__wfGraph = graph;

            // Load canvas data
            this.loadCanvas(graph);

            // Listen for graph events
            window.addEventListener('wf:node-selected', (e) => {
                // In run view mode, show step popover instead of config panel
                if (graph._runViewMode && graph._runViewSteps) {
                    const nodeId = e.detail.nodeId;
                    const step = graph._runViewSteps.find(s => s.node_id === nodeId);
                    if (step) {
                        const node = graph.getCellById(nodeId);
                        if (node) {
                            const bbox = node.getBBox();
                            const pos = graph.localToPage({ x: bbox.x + bbox.width + 10, y: bbox.y });
                            this.stepPopover = { visible: true, x: pos.x, y: pos.y, step: step };
                        }
                    }
                    return;
                }

                // Close block picker when selecting a node — config panel takes over
                this.blockPickerOpen = false;

                this.selectedNode = e.detail.data;
                this.nodeData = e.detail.data;
                this.selectedNodeId = e.detail.nodeId;
                this.panelView = 'config';
                this.panelOpen = true;

                // Dispatch to Livewire config panel
                if (window.Livewire) {
                    const data = e.detail.data || {};
                    window.Livewire.dispatch('node-selected', {
                        nodeId: data.nodeId || e.detail.nodeId,
                        nodeType: data.type || 'unknown',
                        actionType: data.actionType || null,
                    });
                }
            });

            window.addEventListener('wf:node-deselected', () => {
                this.selectedNode = null;
                this.nodeData = null;
                this.selectedNodeId = null;
                if (this.panelView === 'config') {
                    this.panelOpen = false;
                    this.panelView = null;
                }

                // Dispatch to Livewire config panel
                if (window.Livewire) {
                    window.Livewire.dispatch('node-deselected');
                }
            });

            // Listen for Livewire config updates and sync back to X6 graph
            window.addEventListener('node-config-saved', (e) => {
                const { nodeId, config } = e.detail || {};
                if (!nodeId || !graph) return;

                const node = graph.getCellById(nodeId);
                if (node) {
                    const currentData = node.getData() || {};
                    node.setData({ ...currentData, config: config }, { overwrite: true });
                    this.isDirty = true;

                    // Auto-propagate entity type when trigger config is saved
                    const savedNodeData = node.getData() || {};
                    if (savedNodeData.type === 'trigger' && savedNodeData.config?.entity_type) {
                        this.propagateEntityType(savedNodeData.config.entity_type);
                    }

                    // Force re-render of downstream nodes when trigger entity changes
                    if (savedNodeData.type === 'trigger') {
                        graph.getNodes().forEach(n => {
                            if (n.id !== nodeId) {
                                const d = n.getData() || {};
                                n.setData({ ...d, _renderKey: Date.now() }, { overwrite: true });
                            }
                        });
                    }
                }
            });

            // Save shortcut
            window.addEventListener('wf:save-requested', () => {
                this.saveCanvas();
            });

            // Open block picker from canvas double-click
            window.addEventListener('wf:open-picker', () => {
                this.openBlockPicker();
            });

            // Mode toggle shortcuts
            window.addEventListener('wf:set-mode', (e) => {
                this.setMode(e.detail);
            });

            // Run view events
            window.addEventListener('wf:enter-run-view', (e) => {
                enterRunView(graph, e.detail);
            });

            window.addEventListener('wf:exit-run-view', () => {
                exitRunView(graph);
            });

            // Track total run count for badge
            window.addEventListener('wf:runs-loaded', (e) => {
                this.totalRunCount = e.detail.total || 0;
            });

            // Track node count for empty canvas onboarding
            graph.on('cell:added', () => {
                this.hasNodes = graph.getNodes().length > 0;
                ensureEdgesBehindNodes(graph);
            });
            graph.on('cell:removed', () => { this.hasNodes = graph.getNodes().length > 0; });

            // Track dirty state (suppressed during canvas load)
            this._isLoading = false;
            const markDirty = () => { if (!this._isLoading) this.isDirty = true; };
            graph.on('cell:added', markDirty);
            graph.on('cell:removed', markDirty);
            graph.on('cell:changed', markDirty);
            graph.on('node:moved', markDirty);
            graph.on('edge:connected', markDirty);
            graph.on('edge:removed', markDirty);

            // Warn before leaving with unsaved changes
            window.addEventListener('beforeunload', (e) => {
                if (this.isDirty) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // SPA navigation guard (Livewire wire:navigate)
            document.addEventListener('livewire:navigating', (e) => {
                if (this.isDirty) {
                    if (!confirm('You have unsaved changes. Leave without saving?')) {
                        e.preventDefault();
                    }
                }
            });

            // Edge add button
            window.addEventListener('wf:show-edge-add', (e) => {
                this.edgeAddBtn = { visible: true, ...e.detail };
            });
            window.addEventListener('wf:hide-edge-add', () => {
                setTimeout(() => { if (!this._edgeAddHover) this.edgeAddBtn.visible = false; }, 200);
            });

            // Track zoom level
            graph.on('scale', ({ sx }) => {
                this.zoomLevel = sx;
            });

            // Add-step placeholder position tracking
            window.addEventListener('wf:placeholder-update', (e) => {
                this.addStepPlaceholder = e.detail;
            });
        },

        // ── Panel Management ─────────────────────────────────

        togglePanel(view) {
            if (this.panelView === view && this.panelOpen) {
                this.closePanel();
            } else {
                this.panelView = view;
                this.panelOpen = true;
            }
        },

        closePanel() {
            this.panelOpen = false;
            this.panelView = null;
        },

        deselectNode() {
            this.selectedNode = null;
            this.selectedNodeId = null;
            this.panelOpen = false;
            this.panelView = null;
            const graph = window.__wfGraph;
            if (graph) graph.cleanSelection();

            // Notify Livewire config panel
            if (window.Livewire) {
                window.Livewire.dispatch('node-deselected');
            }
        },

        // ── Canvas Mode ──────────────────────────────────────

        setMode(mode) {
            this.interactionMode = mode;
            const graph = window.__wfGraph;
            if (!graph) return;

            if (mode === 'hand') {
                graph.disableSelection();
                graph.panning.enablePanning();
            } else {
                graph.enableSelection();
                // Right-click and mouseWheel panning still work in pointer mode
                // but we don't need to disable panning entirely
            }
        },

        // ── Zoom Controls ────────────────────────────────────

        zoomIn() {
            const graph = window.__wfGraph;
            if (graph) graph.zoom(0.1);
        },

        zoomOut() {
            const graph = window.__wfGraph;
            if (graph) graph.zoom(-0.1);
        },

        fitToView() {
            const graph = window.__wfGraph;
            if (graph) {
                graph.zoomToFit({ padding: 60, maxScale: 1.5 });
                graph.centerContent();
            }
        },

        // ── Toolbar Actions ──────────────────────────────────

        undoAction() {
            const graph = window.__wfGraph;
            if (graph) graph.undo();
        },

        redoAction() {
            const graph = window.__wfGraph;
            if (graph) graph.redo();
        },

        organizeBlocks() {
            const graph = window.__wfGraph;
            if (graph) organizeLayout(graph);
        },

        // ── Block Picker ─────────────────────────────────────

        openBlockPicker(sourceNodeId) {
            this.blockPickerSourceNode = sourceNodeId || null;
            this.blockPickerSearch = '';
            this.blockPickerHighlightKey = '';
            this.blockPickerOpen = true;
            // Close any other panel view — block picker takes over the sidebar
            this.panelOpen = false;
            this.panelView = null;
            this.selectedNode = null;
            this.selectedNodeId = null;
            const graph = window.__wfGraph;
            if (graph) graph.cleanSelection();
            if (window.Livewire) {
                window.Livewire.dispatch('node-deselected');
            }
            this.$nextTick(() => {
                this.$refs.pickerSearchInput?.focus();
            });
        },

        pickerKeydown(event) {
            const flat = this.flatPickerBlocks;
            if (event.key === 'Escape') {
                this.blockPickerOpen = false;
                return;
            }
            if (flat.length === 0) return;
            const keyOf = b => b.type + (b.actionType || '');
            const currentIdx = flat.findIndex(b => keyOf(b) === this.blockPickerHighlightKey);
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                const nextIdx = currentIdx < flat.length - 1 ? currentIdx + 1 : 0;
                this.blockPickerHighlightKey = keyOf(flat[nextIdx]);
                this.$nextTick(() => document.querySelector('.wf-picker-item-hl')?.scrollIntoView({ block: 'nearest' }));
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                const prevIdx = currentIdx > 0 ? currentIdx - 1 : flat.length - 1;
                this.blockPickerHighlightKey = keyOf(flat[prevIdx]);
                this.$nextTick(() => document.querySelector('.wf-picker-item-hl')?.scrollIntoView({ block: 'nearest' }));
            } else if (event.key === 'Enter' && this.blockPickerHighlightKey) {
                event.preventDefault();
                const block = flat.find(b => keyOf(b) === this.blockPickerHighlightKey);
                if (block) this.addBlock(block);
            }
        },

        addBlock(block) {
            const graph = window.__wfGraph;
            if (!graph) return;

            if (this._insertOnEdge) {
                this.edgeAddBtn.edgeId = this._insertOnEdge;
                this._insertOnEdge = null;
                this.insertBlockOnEdge(block);
                return;
            }

            // Sequential placement: find the last node and place below it, aligned
            const nodes = graph.getNodes();
            const VERTICAL_SPACING = 120;
            let sourceNodeId = this.blockPickerSourceNode;

            if (nodes.length === 0) {
                // First node: place at canvas center (convert page coords to graph coords)
                const container = document.getElementById('workflow-canvas');
                const rect = container ? container.getBoundingClientRect() : { left: 0, top: 0, width: 600, height: 400 };
                const centerPage = { x: rect.left + rect.width / 2, y: rect.top + rect.height / 3 };
                const graphPt = graph.pageToLocal(centerPage.x, centerPage.y);
                const newNode = addBlockToGraph(graph, block, null, null, { x: graphPt.x - 140, y: graphPt.y });
                this.blockPickerOpen = false;
                return;
            }

            // Find the bottommost node
            let bottomNode = null;
            let maxBottom = -Infinity;
            for (const n of nodes) {
                const pos = n.getPosition();
                const size = n.getSize();
                const bottom = pos.y + size.height;
                if (bottom > maxBottom) {
                    maxBottom = bottom;
                    bottomNode = n;
                }
            }

            // Auto-connect to the bottommost node if no explicit source
            if (!sourceNodeId && bottomNode) {
                const bottomData = bottomNode.getData() || {};
                if (bottomData.type !== 'stop') {
                    sourceNodeId = bottomNode.id;
                }
            }

            // Let addBlockToGraph handle positioning relative to sourceNodeId
            const newNode = addBlockToGraph(graph, block, sourceNodeId, null, null);
            this.blockPickerOpen = false;

            // Auto-connect if no sourceNodeId was set but there's a node with a free output
            if (!sourceNodeId && newNode && nodes.length > 0) {
                const newNodeData = newNode.getData() || {};
                if (newNodeData.type !== 'trigger') {
                    const allEdges = graph.getEdges();
                    const nodesWithFreeOutput = nodes.filter(n => {
                        if (n.id === newNode.id) return false;
                        const data = n.getData() || {};
                        if (data.type === 'stop') return false;
                        const hasOutgoing = allEdges.some(e => {
                            const source = e.getSourceCell();
                            return source && source.id === n.id;
                        });
                        return !hasOutgoing;
                    });

                    if (nodesWithFreeOutput.length === 1) {
                        const sourceNode = nodesWithFreeOutput[0];
                        const sourcePort = sourceNode.getData()?.type === 'condition' ? 'out-yes' : 'out';
                        const autoEdgeConfig = {
                            source: { cell: sourceNode.id, port: sourcePort },
                            target: { cell: newNode.id, port: 'in' },
                            zIndex: -1,
                            attrs: {
                                line: {
                                    stroke: '#94a3b8',
                                    strokeWidth: 1.5,
                                    targetMarker: { name: 'block', width: 6, height: 4 },
                                },
                            },
                        };

                        if (sourceNode.getData()?.type === 'condition') {
                            const isYes = sourcePort === 'out-yes';
                            const labelText = isYes ? 'Yes' : 'No';
                            autoEdgeConfig.labels = [{
                                attrs: {
                                    label: { text: labelText, fill: '#94a3b8', fontSize: 11, fontWeight: 500 },
                                    rect: { ref: 'label', fill: '#fff', rx: 4, ry: 4, refWidth: '120%', refHeight: '140%', refX: '-10%', refY: '-20%', stroke: '#e2e8f0', strokeWidth: 1 },
                                },
                            }];
                            autoEdgeConfig.attrs.line.stroke = isYes ? '#22c55e' : '#ef4444';
                        }

                        graph.addEdge(autoEdgeConfig);
                    }
                }
            }
        },

        insertBlockOnEdge(block) {
            const graph = window.__wfGraph;
            if (!graph || !this.edgeAddBtn.edgeId) return;

            const edge = graph.getCellById(this.edgeAddBtn.edgeId);
            if (!edge) return;

            const sourceId = edge.getSourceCellId();
            const targetId = edge.getTargetCellId();
            const sourcePortId = edge.getSourcePortId();
            const targetPortId = edge.getTargetPortId();

            graph.removeCell(edge);

            const sourceNode = graph.getCellById(sourceId);
            const targetNode = graph.getCellById(targetId);
            let midX = 300, midY = 300;
            if (sourceNode && targetNode) {
                const sp = sourceNode.getPosition();
                const tp = targetNode.getPosition();
                midX = (sp.x + tp.x) / 2;
                midY = (sp.y + tp.y) / 2;
            }

            const pos = { x: midX, y: midY };
            const newNode = addBlockToGraph(graph, block, null, null, pos);
            if (!newNode) return;

            graph.addEdge({ source: { cell: sourceId, port: sourcePortId || 'out' }, target: { cell: newNode.id, port: 'in' }, zIndex: -1 });
            graph.addEdge({ source: { cell: newNode.id, port: 'out' }, target: { cell: targetId, port: targetPortId || 'in' }, zIndex: -1 });
            requestAnimationFrame(() => ensureEdgesBehindNodes(graph));

            this.edgeAddBtn.visible = false;
            this.blockPickerOpen = false;
        },

        // ── Canvas Load / Save ───────────────────────────────

        async loadCanvas(graph) {
            this._isLoading = true;
            try {
                const response = await fetch(`/workflow/api/workflows/${this.workflowId}/canvas`);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.json();

                // Store meta for variable picker and manifest for connection rules
                window.__wfMeta = data.meta || {};
                window.__wfManifest = data.manifest || {};
                this.registeredActions = data.meta?.registered_actions || {};
                this.workflowDescription = data.meta?.description || '';
                this.triggerType = data.meta?.trigger_type || '';
                this.maxStepsPerRun = data.meta?.trigger_config?.max_steps || 100;
                this.notifyOnFailure = data.meta?.trigger_config?.notify_on_failure || false;

                if (data.nodes?.length) {
                    // Add nodes
                    data.nodes.forEach((node) => {
                        graph.addNode({
                            id: node.node_id,
                            shape: SHAPE_MAP[node.type] || 'workflow-action',
                            x: node.position_x || 100,
                            y: node.position_y || 100,
                            zIndex: 10,
                            data: {
                                type: node.type,
                                nodeId: node.node_id,
                                config: node.config || {},
                                actionType: node.action_type,
                            },
                        });
                    });

                    // Add edges
                    data.edges?.forEach((edge) => {
                        const label = edge.condition_label;
                        const lower = label ? label.toLowerCase() : '';
                        const isYes = lower === 'yes' || lower === 'does match';
                        let labelConfig = [];
                        if (label) {
                            const displayLabel = isYes ? 'Yes' : 'No';
                            labelConfig = [{
                                attrs: {
                                    label: {
                                        text: displayLabel,
                                        fill: '#fff',
                                        fontSize: 11,
                                        fontWeight: 600,
                                    },
                                    rect: {
                                        ref: 'label',
                                        fill: isYes ? '#22c55e' : '#ef4444',
                                        rx: 10,
                                        ry: 10,
                                        refWidth: '140%',
                                        refHeight: '140%',
                                        refX: '-20%',
                                        refY: '-20%',
                                    },
                                },
                            }];
                        }

                        graph.addEdge({
                            id: edge.edge_id,
                            source: edge.source_node_id,
                            target: edge.target_node_id,
                            zIndex: -1,
                            labels: labelConfig,
                            attrs: {
                                line: {
                                    stroke: label ? (isYes ? '#22c55e' : '#ef4444') : '#94a3b8',
                                    strokeWidth: 1.5,
                                    targetMarker: { name: 'block', width: 10, height: 6 },
                                },
                            },
                        });
                    });

                    // Fit to view
                    graph.zoomToFit({ padding: 60, maxScale: 1.5 });

                    // Ensure edges render behind nodes (SVG paint order)
                    // Called after zoomToFit to avoid X6 re-rendering undoing the order
                    requestAnimationFrame(() => ensureEdgesBehindNodes(graph));
                }

                if (data.canvas_data?.zoom) {
                    graph.zoomTo(data.canvas_data.zoom);
                }

                this.hasNodes = graph.getNodes().length > 0;
            } catch (error) {
                console.error('Failed to load canvas:', error);
                showToast('Failed to load workflow canvas.', 'error');
            } finally {
                this._isLoading = false;
                this.isDirty = false;
            }
        },

        async saveCanvas() {
            const graph = window.__wfGraph;
            if (!graph) return;

            clearValidationErrors(graph);
            const { errors: validationErrors, warnings: validationWarnings } = validateAllNodes(graph, true);
            if (validationErrors.length > 0) {
                showToast(`${validationErrors.length} node(s) need configuration before saving.`, 'error');
                return;
            }
            if (validationWarnings.length > 0) {
                showToast(`${validationWarnings.length} warning(s): ${validationWarnings[0].message}${validationWarnings.length > 1 ? ` (+${validationWarnings.length - 1} more)` : ''}`, 'warning');
            }

            const cells = graph.getCells();
            const nodes = [];
            const edges = [];

            cells.forEach((cell) => {
                if (cell.isNode()) {
                    const data = cell.getData() || {};
                    const pos = cell.getPosition();
                    nodes.push({
                        node_id: cell.id,
                        type: data.type || 'action',
                        action_type: data.actionType || data.config?.action_type || null,
                        config: data.config || {},
                        position_x: Math.round(pos.x),
                        position_y: Math.round(pos.y),
                    });
                } else if (cell.isEdge()) {
                    const source = cell.getSourceCellId();
                    const target = cell.getTargetCellId();
                    const labels = cell.getLabels();
                    edges.push({
                        edge_id: cell.id,
                        source_node_id: source,
                        target_node_id: target,
                        condition_label: labels?.[0]?.attrs?.label?.text || null,
                    });
                }
            });

            this.saving = true;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response = await fetch(`/workflow/api/workflows/${this.workflowId}/canvas`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        canvas_data: { zoom: graph.zoom() },
                        nodes,
                        edges,
                    }),
                });

                if (response.ok) {
                    this.isDirty = false;
                    showToast('Workflow saved successfully.', 'success');
                } else {
                    showToast('Failed to save. Your changes are preserved.', 'error');
                }
            } catch (err) {
                console.error('Failed to save canvas:', err);
                showToast('Failed to save. Your changes are preserved.', 'error');
            } finally {
                this.saving = false;
            }
        },

        async runTestRun() {
            if (this.testRunning) return;

            // Save first if dirty
            if (this.isDirty) {
                await this.saveCanvas();
            }

            this.testRunning = true;
            this.testRunResults = null;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response = await fetch(`/workflow/api/workflows/${this.workflowId}/test-run`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ context: {} }),
                });

                const data = await response.json();

                if (response.ok) {
                    this.testRunResults = data;
                    showToast(`Test run ${data.status}: ${(data.steps || []).length} step(s) traced.`, data.status === 'completed' ? 'success' : 'error');
                } else {
                    this.testRunResults = { status: 'failed', error: data.error || 'Test run failed', steps: [] };
                    showToast(data.error || 'Test run failed.', 'error');
                }
            } catch (err) {
                console.error('Test run failed:', err);
                this.testRunResults = { status: 'failed', error: err.message, steps: [] };
                showToast('Test run failed.', 'error');
            } finally {
                this.testRunning = false;
            }
        },

        // ── Entity Type Propagation ──────────────────────────

        propagateEntityType(entityType) {
            const graph = window.__wfGraph;
            if (!graph) return;

            const entityActions = ['create_record', 'update_record', 'find_record', 'delete_record'];

            graph.getNodes().forEach(node => {
                const data = node.getData() || {};

                // Propagate to record action nodes
                if (data.type === 'action' && entityActions.includes(data.actionType)) {
                    const config = data.config || {};
                    if (!config._entityOverridden) {
                        node.setData({
                            ...data,
                            config: { ...config, entity_type: entityType },
                        }, { overwrite: true });
                    }
                }

                // Store trigger entity context on condition/loop nodes
                if (['condition', 'loop'].includes(data.type)) {
                    const config = data.config || {};
                    node.setData({
                        ...data,
                        config: { ...config, _triggerEntity: entityType },
                    }, { overwrite: true });
                }
            });
        },

        // ── Toast Helper ─────────────────────────────────────

        showToast(message, type) {
            showToast(message, type);
        },
    };
}

function registerAlpineComponents(Alpine) {
    Alpine.data('workflowBuilder', workflowBuilderFactory);
    Alpine.data('runHistory', (workflowId) => runHistoryComponent(workflowId));
}

// Handle both pre- and post-Alpine initialization scenarios.
// In Filament/Livewire, Alpine may already be started by the time this
// bundle loads (script in @push('scripts') runs after @filamentScripts).
if (window.Alpine) {
    // Alpine already available — register components immediately
    registerAlpineComponents(window.Alpine);

    // Re-initialize any x-data elements that Alpine already tried to process
    // but failed because our components weren't registered yet
    document.querySelectorAll('[x-data*="workflowBuilder"], [x-data*="runHistory"]').forEach((el) => {
        if (!el._x_dataStack) {
            window.Alpine.initTree(el);
        }
    });
} else {
    // Alpine not yet loaded — listen for its init event
    document.addEventListener('alpine:init', () => {
        if (window.Alpine) registerAlpineComponents(window.Alpine);
    });
}
