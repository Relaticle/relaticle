<div>
    {{-- Side Panel --}}
    <div
        x-data="{
            open: @entangle('isOpen'),
            width: parseInt(localStorage.getItem('chat-panel-width') || '420'),
            minWidth: 360,
            maxWidth: 720,
            resizing: false,

            init() {
                window.addEventListener('keydown', (e) => {
                    if ((e.metaKey || e.ctrlKey) && e.key === 'j') {
                        e.preventDefault();
                        this.open = !this.open;
                    }
                });

                window.addEventListener('chat:send', (e) => {
                    if (e.detail?.message) {
                        this.open = true;
                        $wire.handleSendFromDashboard(e.detail.message, e.detail.source ?? 'dashboard');
                    }
                });

                document.addEventListener('livewire:navigated', () => {
                    $wire.refreshContext();
                });
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
        class="fixed inset-y-0 right-0 z-50 flex"
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
                    class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                >
                    <x-heroicon-o-x-mark class="h-5 w-5" />
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
            class="flex items-center gap-2 rounded-full bg-primary-600 px-4 py-3 text-white shadow-lg transition hover:bg-primary-700 hover:shadow-xl"
            title="Open Chat (Cmd+J)"
            data-chat-toggle
        >
            <x-heroicon-s-chat-bubble-left-right class="h-5 w-5" />
            <span class="text-sm font-medium">Chat</span>
            <kbd class="hidden items-center rounded bg-primary-500 px-1.5 py-0.5 font-mono text-xs text-primary-100 sm:inline-flex">
                &#8984;J
            </kbd>
        </button>
    </div>
</div>
