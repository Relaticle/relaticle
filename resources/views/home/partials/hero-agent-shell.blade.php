{{-- Mock Filament app shell sidebar — visible from md: up, hidden on mobile --}}
<aside class="hidden md:flex md:w-48 lg:w-56 shrink-0 flex-col border-r border-gray-200/60 dark:border-white/[0.06] bg-gray-50/60 dark:bg-white/[0.015]">
    {{-- Workspace switcher --}}
    <div class="flex items-center gap-2 px-3 py-3 border-b border-gray-200/60 dark:border-white/[0.06]">
        <div class="w-6 h-6 rounded-md bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shrink-0">
            <span class="text-[10px] font-bold text-white">RT</span>
        </div>
        <div class="flex-1 min-w-0">
            <div class="text-[11px] font-semibold text-gray-900 dark:text-white truncate">Relaticle</div>
            <div class="text-[9px] text-gray-500 dark:text-gray-500 truncate">Workspace</div>
        </div>
        <x-ri-arrow-down-s-line class="w-3 h-3 text-gray-400 dark:text-gray-500"/>
    </div>

    {{-- Nav items --}}
    <nav class="flex-1 px-2 py-3 space-y-0.5 text-[12px]">
        <div class="flex items-center gap-2 px-2 py-1.5 rounded-md text-gray-600 dark:text-gray-400">
            <x-ri-dashboard-line class="w-3.5 h-3.5 shrink-0"/>
            <span>Dashboard</span>
        </div>
        <div class="flex items-center gap-2 px-2 py-1.5 rounded-md text-gray-600 dark:text-gray-400">
            <x-ri-user-3-line class="w-3.5 h-3.5 shrink-0"/>
            <span>People</span>
        </div>
        <div class="flex items-center gap-2 px-2 py-1.5 rounded-md text-gray-600 dark:text-gray-400">
            <x-ri-building-line class="w-3.5 h-3.5 shrink-0"/>
            <span>Companies</span>
        </div>
        <div class="flex items-center gap-2 px-2 py-1.5 rounded-md text-gray-600 dark:text-gray-400">
            <x-ri-funds-line class="w-3.5 h-3.5 shrink-0"/>
            <span>Opportunities</span>
        </div>
        <div class="flex items-center gap-2 px-2 py-1.5 rounded-md text-gray-600 dark:text-gray-400">
            <x-ri-check-double-line class="w-3.5 h-3.5 shrink-0"/>
            <span>Tasks</span>
        </div>
        <div class="flex items-center gap-2 px-2 py-1.5 rounded-md text-gray-600 dark:text-gray-400">
            <x-ri-sticky-note-line class="w-3.5 h-3.5 shrink-0"/>
            <span>Notes</span>
        </div>
        <div id="hero-shell-nav-chat-active" class="flex items-center gap-2 px-2 py-1.5 rounded-md bg-primary/10 dark:bg-primary/15 text-primary-700 dark:text-primary-300 font-medium">
            <x-ri-sparkling-2-fill class="w-3.5 h-3.5 shrink-0"/>
            <span>Chat</span>
        </div>
    </nav>

    {{-- Recent chats --}}
    <div class="px-2 pb-3 border-t border-gray-200/60 dark:border-white/[0.06] pt-3">
        <div class="px-2 text-[9px] uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1.5">Recent</div>
        <div class="px-2 py-1 text-[11px] text-gray-500 dark:text-gray-500 truncate">Overdue tasks this week</div>
        <div class="px-2 py-1 text-[11px] text-gray-500 dark:text-gray-500 truncate">Q1 pipeline review</div>
    </div>
</aside>
