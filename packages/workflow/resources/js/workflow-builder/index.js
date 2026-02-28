import { createGraph } from './graph.js';
import { registerTriggerNode } from './nodes/TriggerNode.js';
import { registerActionNode } from './nodes/ActionNode.js';
import { registerConditionNode } from './nodes/ConditionNode.js';
import { registerDelayNode } from './nodes/DelayNode.js';
import { registerLoopNode } from './nodes/LoopNode.js';
import { registerStopNode } from './nodes/StopNode.js';
import { initSidebar } from './sidebar.js';
import { initToolbar } from './toolbar.js';
import { initConfigPanel } from './config-panel.js';

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `workflow-toast workflow-toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('workflow-builder-app');
    if (!app) return;

    const workflowId = app.dataset.workflowId;
    const container = document.getElementById('workflow-canvas');
    const minimapContainer = document.getElementById('workflow-minimap');

    // Register custom node shapes
    registerTriggerNode();
    registerActionNode();
    registerConditionNode();
    registerDelayNode();
    registerLoopNode();
    registerStopNode();

    // Create the X6 graph
    const graph = createGraph(container, minimapContainer);

    // Initialize UI components
    initSidebar(graph);
    initToolbar(graph);
    initConfigPanel(graph);

    // Load existing canvas data from the API
    loadCanvas(graph, workflowId);

    // Wire up the save button
    document.getElementById('btn-save')?.addEventListener('click', () => {
        saveCanvas(graph, workflowId);
    });
});

async function loadCanvas(graph, workflowId) {
    try {
        const response = await fetch(`/workflow/api/workflows/${workflowId}/canvas`);
        if (!response.ok) return;

        const data = await response.json();

        if (data.nodes?.length) {
            const shapeMap = {
                trigger: 'workflow-trigger',
                action: 'workflow-action',
                condition: 'workflow-condition',
                delay: 'workflow-delay',
                loop: 'workflow-loop',
                stop: 'workflow-stop',
            };

            // Add nodes
            data.nodes.forEach((node) => {
                graph.addNode({
                    id: node.node_id,
                    shape: shapeMap[node.type] || 'workflow-action',
                    x: node.position_x || 100,
                    y: node.position_y || 100,
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
                graph.addEdge({
                    id: edge.edge_id,
                    source: edge.source_node_id,
                    target: edge.target_node_id,
                    labels: edge.condition_label
                        ? [{ attrs: { label: { text: edge.condition_label } } }]
                        : [],
                    attrs: {
                        line: {
                            stroke: '#A2B1C3',
                            strokeWidth: 2,
                            targetMarker: {
                                name: 'block',
                                width: 12,
                                height: 8,
                            },
                        },
                    },
                });
            });
        }

        // Restore view: fit to content then apply saved zoom
        if (data.nodes?.length) {
            graph.zoomToFit({ padding: 60, maxScale: 1.5 });
        }
        if (data.canvas_data?.zoom) {
            graph.zoomTo(data.canvas_data.zoom);
        }
    } catch (err) {
        console.error('Failed to load canvas:', err);
    }
}

async function saveCanvas(graph, workflowId) {
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

    const btn = document.getElementById('btn-save');

    try {
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.content || '';

        const response = await fetch(
            `/workflow/api/workflows/${workflowId}/canvas`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    canvas_data: { zoom: graph.zoom(), scroll: graph.getScrollbarPosition?.() || null },
                    nodes,
                    edges,
                }),
            }
        );

        if (response.ok) {
            showToast('Workflow saved successfully.', 'success');
        } else {
            console.error('Save failed:', response.status, response.statusText);
            showToast('Failed to save workflow. Please try again.', 'error');
        }
    } catch (err) {
        console.error('Failed to save canvas:', err);
        showToast('Failed to save workflow. Please try again.', 'error');
    }
}
