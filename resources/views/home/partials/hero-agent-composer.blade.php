{{-- Composer — card-style 2-row layout, mirrors real chat-interface composer --}}
<div class="mcp-el mcp-input px-4 sm:px-6 md:px-8 pb-4">
    <div class="relative rounded-2xl border border-gray-200 bg-white transition-colors focus-within:border-primary-500 dark:border-white/[0.06] dark:bg-gray-900">
        {{-- Editor row — placeholder + cursor sit tight; remaining row width is empty space (like a real text input). --}}
        <div class="flex items-center px-4 pt-3.5 pb-1.5">
            <span class="text-sm text-gray-400 dark:text-gray-500">Ask anything…</span>
            <span id="hero-composer-cursor" class="hero-composer-cursor block w-px h-4 bg-primary/60 dark:bg-primary/80 ml-px" aria-hidden="true"></span>
        </div>

        {{-- Footer row: model picker on the left, send button on the right --}}
        <div class="flex items-center justify-between gap-2 px-3 pb-2.5">
            <button type="button" tabindex="-1" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-pico font-medium text-gray-500 dark:text-gray-400">
                <span>Auto</span>
                <x-heroicon-o-chevron-down class="w-3 h-3"/>
            </button>
            <button id="hero-composer-send" type="button" tabindex="-1" aria-hidden="true" class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-primary-600 text-white">
                <x-heroicon-s-arrow-up class="w-4 h-4"/>
            </button>
        </div>
    </div>
</div>

<style>
    .hero-composer-cursor { animation: hero-composer-blink 1.05s steps(2, end) infinite; }
    @keyframes hero-composer-blink { to { visibility: hidden; } }
    @media (prefers-reduced-motion: reduce) {
        .hero-composer-cursor { animation: none; }
    }
</style>
