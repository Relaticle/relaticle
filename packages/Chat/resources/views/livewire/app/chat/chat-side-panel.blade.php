<div>
    {{-- Side Panel --}}
    <div
        x-data="{
            open: @entangle('isOpen'),
            width: Math.max(360, Math.min(720, parseInt(localStorage.getItem('chat-panel-width') || '420', 10) || 420)),
            minWidth: 360,
            maxWidth: 720,
            resizing: false,

            init() {
                this.keydownHandler = (e) => {
                    if ((e.metaKey || e.ctrlKey) && e.key === 'j') {
                        e.preventDefault();
                        this.open = !this.open;
                        return;
                    }
                    if (e.key === 'Escape' && this.open) {
                        e.preventDefault();
                        this.open = false;
                    }
                };
                window.addEventListener('keydown', this.keydownHandler);

                this.chatSendHandler = (e) => {
                    if (e.detail?.message) {
                        this.open = true;
                        $wire.handleSendFromDashboard(e.detail.message, e.detail.source ?? 'dashboard');
                    }
                };
                window.addEventListener('chat:send', this.chatSendHandler);

                this.navigatedHandler = () => {
                    $wire.refreshContext();
                };
                document.addEventListener('livewire:navigated', this.navigatedHandler);
            },

            destroy() {
                window.removeEventListener('keydown', this.keydownHandler);
                window.removeEventListener('chat:send', this.chatSendHandler);
                document.removeEventListener('livewire:navigated', this.navigatedHandler);
            },

            startResize(e) {
                this.resizing = true;
                const startX = e.clientX;
                const startWidth = this.width;

                const onMouseMove = (moveEvent) => {
                    const delta = startX - moveEvent.clientX;
                    this.width = Math.max(this.minWidth, Math.min(this.maxWidth, startWidth + delta));
                };

                const onMouseUp = () => {
                    this.resizing = false;
                    localStorage.setItem('chat-panel-width', this.width.toString());
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                };

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            }
        }"
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        x-cloak
        role="dialog"
        aria-modal="true"
        aria-label="Chat side panel"
        tabindex="-1"
        class="fixed inset-y-0 right-0 z-50 flex max-w-full"
        :style="{ width: width + 'px' }"
        data-chat-side-panel
    >
        {{-- Resize Handle --}}
        <div
            @mousedown="startResize($event)"
            class="w-1 cursor-col-resize bg-gray-200 transition-colors hover:bg-primary-400 dark:bg-gray-700 dark:hover:bg-primary-600"
            :class="{ 'bg-primary-500 dark:bg-primary-500': resizing }"
        ></div>

        {{-- Panel Content --}}
        <div class="flex flex-1 flex-col overflow-hidden border-l border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900">
            {{-- Panel Header --}}
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chat-bubble-left-right class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Chat</h3>
                </div>
                <button
                    @click="open = false"
                    type="button"
                    aria-label="Close chat panel"
                    class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                >
                    <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" />
                </button>
            </div>

            {{-- Chat Content Area --}}
            <div class="flex-1 overflow-y-auto" data-chat-messages>
                @livewire('chat.chat-interface', [
                    'conversationId' => $conversationId,
                ], key('side-panel-chat-' . ($conversationId ?? 'new')))
            </div>

            {{-- Context-Aware Suggested Prompts --}}
            @if(!empty($suggestedPrompts))
                <div class="border-t border-gray-200 px-4 py-2 dark:border-gray-700">
                    <x-chat.suggested-prompts :prompts="$suggestedPrompts" />
                </div>
            @endif
        </div>
    </div>

    {{-- Floating Toggle Button (visible when panel is closed) --}}
    <div
        x-data="{ panelOpen: @entangle('isOpen') }"
        x-show="!panelOpen"
        x-transition
        class="fixed bottom-6 right-6 z-40"
    >
        <button
            wire:click="togglePanel"
            type="button"
            x-data="{ isMac: navigator.platform.toLowerCase().includes('mac') }"
            :aria-label="isMac ? 'Open chat panel (Cmd+J)' : 'Open chat panel (Ctrl+J)'"
            :title="isMac ? 'Open Chat (Cmd+J)' : 'Open Chat (Ctrl+J)'"
            class="flex items-center gap-2 rounded-full bg-primary-600 px-4 py-3 text-white shadow-lg transition hover:bg-primary-700 hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
            data-chat-toggle
        >
            <x-heroicon-s-chat-bubble-left-right class="h-5 w-5" aria-hidden="true" />
            <span class="text-sm font-medium">Chat</span>
            <kbd class="hidden items-center rounded bg-primary-500 px-1.5 py-0.5 font-mono text-xs text-primary-100 sm:inline-flex" aria-hidden="true">
                <span x-text="isMac ? '⌘J' : 'Ctrl+J'"></span>
            </kbd>
        </button>
    </div>
</div>
