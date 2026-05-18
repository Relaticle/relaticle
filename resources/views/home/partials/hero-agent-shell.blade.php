{{-- Mock Filament app shell sidebar — visible from md: up, hidden on mobile.
     Visually mirrors app.relaticle.test: white bg, dark workspace chip, light-gray
     active state with primary icon (not primary-tinted bg), and a "Chats" group
     at the bottom containing the active conversation.
     Icons use Heroicon outline to match the real Filament app exactly (the rest of
     the marketing site uses Remix Icon per project convention). --}}
<aside class="hidden md:flex md:w-48 lg:w-56 shrink-0 flex-col border-r border-gray-200 bg-white dark:border-white/[0.06] dark:bg-neutral-950">
    {{-- Workspace switcher --}}
    <div class="flex items-center gap-2 px-3 py-3">
        <div class="flex h-7 w-7 items-center justify-center rounded-md bg-gray-900 shrink-0 dark:bg-white/[0.1]">
            <span class="text-pico font-bold text-white">RT</span>
        </div>
        <div class="flex-1 min-w-0">
            <div class="text-xs font-semibold text-gray-900 dark:text-white truncate">Relaticle</div>
        </div>
        <x-heroicon-o-chevron-down class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500"/>
    </div>

    {{-- Top-level nav items — icons match app/Filament/Resources/*Resource.php $navigationIcon --}}
    <nav class="flex-1 overflow-hidden px-2 py-1 space-y-0.5 text-xs">
        <div class="flex items-center gap-2 rounded-md px-2 py-2 text-gray-600 dark:text-gray-400">
            <x-heroicon-o-home class="w-4 h-4 shrink-0"/>
            <span>Home</span>
        </div>
        <div class="flex items-center gap-2 rounded-md px-2 py-2 text-gray-600 dark:text-gray-400">
            <x-heroicon-o-user class="w-4 h-4 shrink-0"/>
            <span>People</span>
        </div>
        <div class="flex items-center gap-2 rounded-md px-2 py-2 text-gray-600 dark:text-gray-400">
            <x-heroicon-o-home-modern class="w-4 h-4 shrink-0"/>
            <span>Companies</span>
        </div>
        <div class="flex items-center gap-2 rounded-md px-2 py-2 text-gray-600 dark:text-gray-400">
            <x-heroicon-o-trophy class="w-4 h-4 shrink-0"/>
            <span>Opportunities</span>
        </div>
        <div class="flex items-center gap-2 rounded-md px-2 py-2 text-gray-600 dark:text-gray-400">
            <x-heroicon-o-check-circle class="w-4 h-4 shrink-0"/>
            <span>Tasks</span>
        </div>
        <div class="flex items-center gap-2 rounded-md px-2 py-2 text-gray-600 dark:text-gray-400">
            <x-heroicon-o-document-text class="w-4 h-4 shrink-0"/>
            <span>Notes</span>
        </div>

        {{-- Chats group --}}
        <div class="pt-4">
            <div class="flex items-center justify-between px-2 pb-1">
                <span class="text-pico uppercase tracking-wider font-medium text-gray-500 dark:text-gray-500">Chats</span>
                <x-heroicon-o-chevron-up class="w-3 h-3 text-gray-400 dark:text-gray-500"/>
            </div>
            {{-- Active chat — mirrors fi-active with light gray bg + primary icon --}}
            <div id="hero-shell-chat-active" class="flex items-center gap-2 rounded-md bg-gray-100 px-2 py-2 font-medium text-gray-900 dark:bg-white/[0.06] dark:text-white">
                <x-heroicon-o-chat-bubble-left-right class="w-4 h-4 shrink-0 text-primary dark:text-primary-400"/>
                <span class="truncate">Overdue tasks this week</span>
            </div>
        </div>
    </nav>
</aside>
