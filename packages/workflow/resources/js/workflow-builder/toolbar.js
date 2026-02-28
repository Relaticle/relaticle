export function initToolbar(graph) {
    document.getElementById('btn-undo')?.addEventListener('click', () => {
        graph.undo();
    });

    document.getElementById('btn-redo')?.addEventListener('click', () => {
        graph.redo();
    });

    document.getElementById('btn-zoom-in')?.addEventListener('click', () => {
        graph.zoom(0.1);
    });

    document.getElementById('btn-zoom-out')?.addEventListener('click', () => {
        graph.zoom(-0.1);
    });

    document.getElementById('btn-fit')?.addEventListener('click', () => {
        graph.zoomToFit({ padding: 40 });
    });
}
