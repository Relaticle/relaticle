<li
    x-data="{ label: 'Chats' }"
    data-group-label="Chats"
    x-bind:class="{ 'fi-collapsed': $store.sidebar.groupIsCollapsed(label) }"
    class="fi-sidebar-group fi-collapsible"
>
    {{-- Group header --}}
    <div
        x-on:click="$store.sidebar.toggleCollapsedGroup(label)"
        x-show="$store.sidebar.isOpen"
        x-transition:enter="fi-transition-enter"
        x-transition:enter-start="fi-transition-enter-start"
        x-transition:enter-end="fi-transition-enter-end"
        class="fi-sidebar-group-btn"
    >
        <span class="fi-sidebar-group-label">Chats</span>

        <x-filament::icon-button
            color="gray"
            :icon="\Filament\Support\Icons\Heroicon::ChevronUp"
            label="Chats"
            x-bind:aria-expanded="! $store.sidebar.groupIsCollapsed(label)"
            x-on:click.stop="$store.sidebar.toggleCollapsedGroup(label)"
            class="fi-sidebar-group-collapse-btn"
        />
    </div>

    {{-- Conversation items --}}
    <ul
        x-show="$store.sidebar.isOpen ? ! $store.sidebar.groupIsCollapsed(label) : true"
        x-collapse.duration.200ms
        x-transition:enter="fi-transition-enter"
        x-transition:enter-start="fi-transition-enter-start"
        x-transition:enter-end="fi-transition-enter-end"
        class="fi-sidebar-group-items"
    >
        @if($conversations->isEmpty())
            <li
                x-show="$store.sidebar.isOpen"
                class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400"
                role="status"
            >
                No chats yet. Start one from the dashboard.
            </li>
        @else
            @foreach($conversations as $conversation)
                @php
                    $chatUrl = \App\Filament\Pages\ChatConversation::getUrl(['conversationId' => $conversation->id]);
                    $isActive = request()->url() === $chatUrl;
                    $renameUrl = route('chat.rename', ['conversationId' => $conversation->id]);
                    $displayTitle = \Illuminate\Support\Str::limit($conversation->title ?: 'Untitled chat', 30);
                    $rawTitle = $conversation->title ?: 'Untitled chat';
                @endphp
                <li
                    x-data="{
                        editing: false,
                        renamed: '',
                        async save() {
                            const text = this.renamed.trim();
                            if (!text) { this.editing = false; return; }
                            try {
                                const res = await fetch(@js($renameUrl), {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '',
                                    },
                                    body: JSON.stringify({ title: text }),
                                });
                                if (res.ok) {
                                    const body = await res.json();
                                    const titleEl = $el.querySelector('[data-title]');
                                    if (titleEl) titleEl.textContent = body.title;
                                }
                            } catch (_) { /* network errors silently abort */ }
                            this.editing = false;
                        },
                        startEdit() {
                            this.renamed = @js($rawTitle);
                            this.editing = true;
                        }
                    }"
                    @class([
                        'fi-sidebar-item group/chat-item relative',
                        'fi-active' => $isActive,
                    ])
                >
                    <template x-if="!editing">
                        <a
                            href="{{ $chatUrl }}"
                            wire:navigate
                            class="fi-sidebar-item-btn pe-16"
                        >
                            <x-heroicon-o-chat-bubble-left class="fi-sidebar-item-icon h-5 w-5" />
                            <span
                                data-title
                                x-show="$store.sidebar.isOpen"
                                x-transition:enter="fi-transition-enter"
                                x-transition:enter-start="fi-transition-enter-start"
                                x-transition:enter-end="fi-transition-enter-end"
                                class="fi-sidebar-item-label truncate"
                            >
                                {{ $displayTitle }}
                            </span>
                        </a>
                    </template>

                    <template x-if="editing">
                        <form
                            @submit.prevent="save()"
                            class="flex items-center gap-2 px-3 py-1.5"
                        >
                            <input
                                type="text"
                                x-model="renamed"
                                @keydown.escape.prevent="editing = false"
                                @click.stop
                                @blur="editing = false"
                                x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                maxlength="255"
                                aria-label="Rename chat"
                                class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            />
                        </form>
                    </template>

                    <button
                        type="button"
                        @click.stop.prevent="startEdit()"
                        x-show="$store.sidebar.isOpen && !editing"
                        aria-label="Rename chat"
                        title="Rename chat"
                        class="absolute inset-y-0 end-7 my-auto flex h-6 w-6 items-center justify-center rounded-md text-gray-400 opacity-0 transition hover:bg-gray-100 hover:text-primary-600 focus:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 group-hover/chat-item:opacity-100 dark:hover:bg-white/5 dark:hover:text-primary-400"
                    >
                        <x-heroicon-o-pencil-square class="h-4 w-4" />
                    </button>

                    <button
                        type="button"
                        wire:click="deleteConversation(@js($conversation->id))"
                        wire:confirm="Delete this chat? Messages and any pending actions will be removed."
                        x-show="$store.sidebar.isOpen && !editing"
                        aria-label="Delete chat"
                        title="Delete chat"
                        class="absolute inset-y-0 end-1 my-auto flex h-6 w-6 items-center justify-center rounded-md text-gray-400 opacity-0 transition hover:bg-gray-100 hover:text-danger-600 focus:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 group-hover/chat-item:opacity-100 dark:hover:bg-white/5 dark:hover:text-danger-400"
                    >
                        <x-heroicon-o-trash class="h-4 w-4" />
                    </button>
                </li>
            @endforeach
        @endif
    </ul>
</li>
