<div
    x-data="{
        open: @entangle('open'),
        width: parseInt(localStorage.getItem('chatPanelWidth') || '420'),
        dragging: false,
        startX: 0,
        startWidth: 0,
        init() {
            document.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'j') {
                    e.preventDefault();
                    this.open = !this.open;
                }
            });
        },
        startResize(e) {
            this.dragging = true;
            this.startX = e.clientX;
            this.startWidth = this.width;
            document.addEventListener('mousemove', this.resize.bind(this));
            document.addEventListener('mouseup', this.stopResize.bind(this));
        },
        resize(e) {
            if (!this.dragging) return;
            const diff = this.startX - e.clientX;
            this.width = Math.min(Math.max(this.startWidth + diff, 320), 800);
        },
        stopResize() {
            this.dragging = false;
            localStorage.setItem('chatPanelWidth', this.width);
            document.removeEventListener('mousemove', this.resize);
            document.removeEventListener('mouseup', this.stopResize);
        }
    }"
    class="relative z-50"
>
    {{-- Toggle Button (fixed position) --}}
    <button
        x-on:click="open = !open"
        x-show="!open"
        class="fixed bottom-6 right-6 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-primary-600 text-white shadow-lg transition hover:bg-primary-500"
        title="Open AI Chat (Cmd+J)"
    >
        <x-heroicon-o-chat-bubble-left-right class="h-6 w-6" />
    </button>

    {{-- Side Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        x-bind:style="'width: ' + width + 'px'"
        class="fixed inset-y-0 right-0 z-40 flex flex-col border-l border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900"
        x-cloak
    >
        {{-- Resize Handle --}}
        <div
            x-on:mousedown="startResize($event)"
            class="absolute inset-y-0 left-0 w-1 cursor-col-resize hover:bg-primary-500/50"
        ></div>

        {{-- Panel Header --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">AI Chat</h3>
            <button
                x-on:click="open = false"
                class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
            >
                <x-heroicon-o-x-mark class="h-5 w-5" />
            </button>
        </div>

        {{-- Chat Content --}}
        <div class="flex-1 overflow-hidden">
            <livewire:chat.chat-interface :conversation-id="$conversationId" />
        </div>
    </div>

    {{-- Backdrop --}}
    <div
        x-show="open"
        x-on:click="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-30 bg-gray-900/25"
        x-cloak
    ></div>
</div>
