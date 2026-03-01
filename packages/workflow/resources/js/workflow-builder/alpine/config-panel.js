/**
 * Alpine.js Config Panel State
 *
 * Provides state properties for the config panel.
 * Form rendering is now handled by the Livewire WorkflowConfigPanel component.
 */
export function configPanelComponent() {
    return {
        nodeData: null,
        selectedNodeId: null,
        registeredActions: null,
    };
}
