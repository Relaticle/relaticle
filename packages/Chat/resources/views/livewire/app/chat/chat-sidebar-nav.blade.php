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
        @foreach($conversations as $conversation)
            @php
                $chatUrl = \App\Filament\Pages\ChatConversation::getUrl(['conversationId' => $conversation->id]);
                $isActive = request()->url() === $chatUrl;
            @endphp
            <li @class([
                'fi-sidebar-item group/chat-item relative',
                'fi-active' => $isActive,
            ])>
                <a
                    href="{{ $chatUrl }}"
                    wire:navigate
                    class="fi-sidebar-item-btn pe-8"
                >
                    <x-heroicon-o-chat-bubble-left class="fi-sidebar-item-icon h-5 w-5" />
                    <span
                        x-show="$store.sidebar.isOpen"
                        x-transition:enter="fi-transition-enter"
                        x-transition:enter-start="fi-transition-enter-start"
                        x-transition:enter-end="fi-transition-enter-end"
                        class="fi-sidebar-item-label truncate"
                    >
                        {{ \Illuminate\Support\Str::limit($conversation->title ?: 'Untitled chat', 30) }}
                    </span>
                </a>

                <button
                    type="button"
                    wire:click="deleteConversation(@js($conversation->id))"
                    wire:confirm="Delete this chat? Messages and any pending actions will be removed."
                    x-show="$store.sidebar.isOpen"
                    aria-label="Delete chat"
                    title="Delete chat"
                    class="absolute inset-y-0 end-1 my-auto flex h-6 w-6 items-center justify-center rounded-md text-gray-400 opacity-0 transition hover:bg-gray-100 hover:text-danger-600 focus:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 group-hover/chat-item:opacity-100 dark:hover:bg-white/5 dark:hover:text-danger-400"
                >
                    <x-heroicon-o-trash class="h-4 w-4" />
                </button>
            </li>
        @endforeach
    </ul>
</li>
