{{-- resources/views/home/partials/hero-agent-composer.blade.php --}}
<div class="mcp-el mcp-input border-t border-[var(--surface-shell-divider)] px-4 sm:px-6 md:px-8 py-3">
    <div class="flex items-center gap-3 bg-[var(--surface-input-bg)] rounded-xl border border-[var(--surface-input-border)] focus-within:border-primary/40 dark:focus-within:border-primary/40 px-3.5 py-2.5">
        <x-ri-sparkling-2-fill class="w-4 h-4 text-gray-400/60 dark:text-gray-500/60 shrink-0"/>
        <span class="text-sm text-gray-400 dark:text-gray-500 flex-1">Ask anything…</span>
        <span id="hero-composer-cursor" class="hero-composer-cursor block w-px h-4 bg-primary/60 dark:bg-primary/80 -ml-3.5" aria-hidden="true"></span>
        <div class="flex items-center gap-1.5">
            <button type="button" tabindex="-1" class="inline-flex items-center gap-1 rounded-md border border-[var(--surface-input-border)] bg-[var(--surface-input-bg)] px-2 py-1 text-[10px] font-medium text-gray-600 dark:text-gray-300">
                <span>Auto</span>
                <x-ri-arrow-down-s-line class="w-3 h-3"/>
            </button>
            <x-ri-mic-line class="w-4 h-4 text-gray-300 dark:text-gray-600" aria-hidden="true"/>
            <button id="hero-composer-send" type="button" tabindex="-1" class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-gray-900 dark:bg-white text-white dark:text-gray-900">
                <x-ri-arrow-up-line class="w-3 h-3"/>
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
