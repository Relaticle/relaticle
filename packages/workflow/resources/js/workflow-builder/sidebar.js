export function initSidebar(graph) {
    const sidebarNodes = document.querySelectorAll('.sidebar-node');

    sidebarNodes.forEach((el) => {
        el.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('nodeType', el.dataset.nodeType);
            e.dataTransfer.effectAllowed = 'move';
        });
    });

    const container = document.getElementById('workflow-canvas-container');

    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    });

    container.addEventListener('drop', (e) => {
        e.preventDefault();
        const nodeType = e.dataTransfer.getData('nodeType');
        if (!nodeType) return;

        const point = graph.clientToLocal(e.clientX, e.clientY);
        addNodeToGraph(graph, nodeType, point.x, point.y);
    });
}

function addNodeToGraph(graph, type, x, y) {
    const shapeMap = {
        trigger: 'workflow-trigger',
        action: 'workflow-action',
        condition: 'workflow-condition',
        delay: 'workflow-delay',
        loop: 'workflow-loop',
        stop: 'workflow-stop',
    };

    const shape = shapeMap[type];
    if (!shape) return;

    const nodeId = `${type}-${Date.now()}`;
    graph.addNode({
        id: nodeId,
        shape,
        x,
        y,
        data: { type, nodeId, config: {} },
    });
}
