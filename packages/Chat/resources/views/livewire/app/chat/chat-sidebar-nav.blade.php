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
                'fi-sidebar-item',
                'fi-active' => $isActive,
            ])>
                <a
                    href="{{ $chatUrl }}"
                    wire:navigate
                    class="fi-sidebar-item-btn"
                >
                    <x-heroicon-o-chat-bubble-left class="fi-sidebar-item-icon h-5 w-5" />
                    <span
                        x-show="$store.sidebar.isOpen"
                        x-transition:enter="fi-transition-enter"
                        x-transition:enter-start="fi-transition-enter-start"
                        x-transition:enter-end="fi-transition-enter-end"
                        class="fi-sidebar-item-label"
                    >
                        {{ \Illuminate\Support\Str::limit($conversation->title ?: 'Untitled chat', 30) }}
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
</li>
