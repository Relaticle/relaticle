<style>.hero-agent-preview .mcp-el { opacity: 0; }</style>

<div x-data="heroChat()"
     @hero-chat-reset.window="cancelInflight(); resetChat()"
     @hero-chat-animate.window="animateChat()"
     @mouseenter="pause()"
     @mouseleave="resume()"
     @focusin="pause()"
     @focusout="resume()"
     class="hero-agent-preview relative bg-white dark:bg-neutral-950 flex h-[520px] sm:h-[580px] md:h-[640px]">

    @include('home.partials.hero-agent-shell')

    {{-- Main pane (chat column) --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- Conversation title bar — mirrors app chat-page header: back chevron, title, overflow --}}
        <div class="px-4 sm:px-6 md:px-8 py-2.5 flex items-center gap-3 text-xs">
            <button type="button" tabindex="-1" aria-hidden="true" class="inline-flex h-6 w-6 items-center justify-center rounded-md text-gray-400 dark:text-gray-500">
                <x-ri-arrow-left-s-line class="w-4 h-4"/>
            </button>
            <span class="font-medium text-gray-900 dark:text-gray-100 truncate flex-1">Overdue tasks this week</span>
            <button type="button" tabindex="-1" aria-hidden="true" class="inline-flex h-6 w-6 items-center justify-center rounded-md text-gray-400 dark:text-gray-500">
                <x-ri-more-2-line class="w-4 h-4"/>
            </button>
        </div>

        {{-- Messages --}}
        <div x-ref="messagesScroll" class="flex-1 overflow-y-auto p-4 sm:p-6 md:px-8 md:py-6 space-y-5 sm:space-y-6 scroll-smooth">
            @include('home.partials.hero-agent-conversation')
        </div>

        @include('home.partials.hero-agent-composer')

    </div>

    {{-- Undo toast — white card to match real app chat undo pattern --}}
    <div class="mcp-el mcp-undo-toast pointer-events-none absolute bottom-20 left-1/2 -translate-x-1/2 z-40 inline-flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-lg dark:border-white/[0.08] dark:bg-gray-800" aria-hidden="true">
        <span class="text-sm text-gray-700 dark:text-gray-300">3 tasks marked complete</span>
        <button type="button" tabindex="-1" class="rounded-md bg-primary-600 px-2 py-1 text-pico font-medium text-white">Undo (5s)</button>
    </div>

</div>

<script>
    function heroChat() {
        return {
            // Mirrors theme.css --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1)
            ease: [0.16, 1, 0.3, 1],
            cycleMs: 22000,
            holdMs: 4000,
            reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            paused: false,
            nextCycleTimer: null,
            scrollTimers: [],

            resetChat() {
                this.$root.querySelectorAll('.mcp-el').forEach(function(el) {
                    el.style.opacity = '0';
                    el.style.transform = '';
                });
                if (this.$refs.messagesScroll) {
                    this.$refs.messagesScroll.scrollTop = 0;
                }
            },

            cancelInflight() {
                this.$root.querySelectorAll('.mcp-el').forEach(function(el) {
                    if (el.getAnimations) {
                        el.getAnimations().forEach(function(a) { a.cancel(); });
                    }
                });
                if (this.nextCycleTimer) {
                    clearTimeout(this.nextCycleTimer);
                    this.nextCycleTimer = null;
                }
                this.scrollTimers.forEach(function(t) { clearTimeout(t); });
                this.scrollTimers = [];
            },

            showAllImmediate() {
                this.$root.querySelectorAll('.mcp-el').forEach(function(el) {
                    el.style.opacity = '1';
                    el.style.transform = '';
                });
                var toast = this.$root.querySelector('.mcp-undo-toast');
                if (toast) toast.style.opacity = '0';
            },

            scrollMessageIntoView(selector) {
                var el = this.$root.querySelector(selector);
                if (!el || !this.$refs.messagesScroll) return;
                var scroller = this.$refs.messagesScroll;
                var target = el.offsetTop - 16;
                scroller.scrollTo({ top: Math.max(0, target), behavior: 'smooth' });
            },

            animateChat() {
                this.cancelInflight();
                this.resetChat();

                if (this.reducedMotion) {
                    this.showAllImmediate();
                    return;
                }

                if (typeof animate !== 'function') {
                    this.showAllImmediate();
                    return;
                }

                this.runCycle();
            },

            runCycle() {
                var root = this.$root;
                var ease = this.ease;
                var self = this;

                animate(root.querySelector('.mcp-input'), { opacity: [0, 1] }, { duration: 0.3, ease: ease });

                // ─── Exchange 1: overdue tasks (t=0.5–4.5) ───
                animate(root.querySelector('.mcp-user-1'),    { opacity: [0, 1], transform: ['translateX(12px)', 'translateX(0px)'] }, { delay: 0.5, duration: 0.4, ease: ease });
                animate(root.querySelector('.mcp-avatar-1'),  { opacity: [0, 1], transform: ['scale(0.8)', 'scale(1)'] }, { delay: 1.0, duration: 0.3, ease: ease });
                animate(root.querySelector('.mcp-label-1'),   { opacity: [0, 1] }, { delay: 1.0, duration: 0.3, ease: ease });
                animate(root.querySelector('.mcp-tool-1'),    { opacity: [0, 1], transform: ['translateX(-6px)', 'translateX(0px)'] }, { delay: 1.3, duration: 0.3, ease: ease });
                animate(root.querySelector('.mcp-text-1'),    { opacity: [0, 1], transform: ['translateY(6px)', 'translateY(0px)'] }, { delay: 1.7, duration: 0.3, ease: ease });
                animate(root.querySelector('.mcp-task-1'),    { opacity: [0, 1], transform: ['translateY(8px)', 'translateY(0px)'] }, { delay: 2.0, duration: 0.35, ease: ease });
                animate(root.querySelector('.mcp-task-2'),    { opacity: [0, 1], transform: ['translateY(8px)', 'translateY(0px)'] }, { delay: 2.3, duration: 0.35, ease: ease });
                animate(root.querySelector('.mcp-task-3'),    { opacity: [0, 1], transform: ['translateY(8px)', 'translateY(0px)'] }, { delay: 2.6, duration: 0.35, ease: ease });

                // ─── Exchange 2: bulk approval (t=5.0–9.0) ───
                // 4900ms = delay 5.0s - 100ms (scroll leads user msg by 100ms so target is in-view when it fades in)
                this.scrollTimers.push(setTimeout(function() { self.scrollMessageIntoView('.mcp-user-2'); }, 4900));
                animate(root.querySelector('.mcp-user-2'),    { opacity: [0, 1], transform: ['translateX(12px)', 'translateX(0px)'] }, { delay: 5.0, duration: 0.4, ease: ease });
                animate(root.querySelector('.mcp-avatar-2'),  { opacity: [0, 1], transform: ['scale(0.8)', 'scale(1)'] }, { delay: 5.5, duration: 0.3, ease: ease });
                animate(root.querySelector('.mcp-label-2'),   { opacity: [0, 1] }, { delay: 5.5, duration: 0.3, ease: ease });
                animate(root.querySelector('.mcp-text-2'),    { opacity: [0, 1], transform: ['translateY(6px)', 'translateY(0px)'] }, { delay: 5.8, duration: 0.3, ease: ease });
                animate(root.querySelector('.mcp-action-card'), { opacity: [0, 1], transform: ['translateY(8px) scale(0.98)', 'translateY(0px) scale(1)'] }, { delay: 6.2, duration: 0.45, ease: ease });

                // Undo toast: in at t=8.0, out at t=11.5
                animate(root.querySelector('.mcp-undo-toast'), { opacity: [0, 1], transform: ['translate(-50%, 16px)', 'translate(-50%, 0px)'] }, { delay: 8.0, duration: 0.35, ease: ease });
                animate(root.querySelector('.mcp-undo-toast'), { opacity: [1, 0] }, { delay: 11.5, duration: 0.4, ease: ease });

                // ─── Exchange 3: create with @-mention (t=12.5–17.0) ───
                // 12400ms = delay 12.5s - 100ms
                this.scrollTimers.push(setTimeout(function() { self.scrollMessageIntoView('.mcp-user-3'); }, 12400));
                animate(root.querySelector('.mcp-user-3'),    { opacity: [0, 1], transform: ['translateX(12px)', 'translateX(0px)'] }, { delay: 12.5, duration: 0.4, ease: ease });
                animate(root.querySelector('.mcp-avatar-3'),  { opacity: [0, 1], transform: ['scale(0.8)', 'scale(1)'] }, { delay: 13.0, duration: 0.3, ease: ease });
                animate(root.querySelector('.mcp-label-3'),   { opacity: [0, 1] }, { delay: 13.0, duration: 0.3, ease: ease });
                animate(root.querySelector('.mcp-tool-3'),    { opacity: [0, 1], transform: ['translateX(-6px)', 'translateX(0px)'] }, { delay: 13.3, duration: 0.3, ease: ease });
                animate(root.querySelector('.mcp-text-3'),    { opacity: [0, 1], transform: ['translateY(6px)', 'translateY(0px)'] }, { delay: 13.7, duration: 0.3, ease: ease });
                animate(root.querySelector('.mcp-card'),      { opacity: [0, 1], transform: ['scale(0.97)', 'scale(1)'] }, { delay: 14.0, duration: 0.4, ease: ease });

                var totalMs = this.cycleMs + this.holdMs;
                this.nextCycleTimer = setTimeout(function() {
                    if (!self.paused) self.animateChat();
                }, totalMs);
            },

            pause() {
                this.paused = true;
                this.cancelInflight();
            },

            resume() {
                if (!this.paused) return;
                this.paused = false;
                this.animateChat();
            }
        };
    }
</script>
