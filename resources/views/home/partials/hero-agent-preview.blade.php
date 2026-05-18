<style>.hero-agent-preview .mcp-el { opacity: 0; }</style>

<div x-data="heroChat()"
     @hero-chat-reset.window="cancelInflight(); resetChat()"
     @hero-chat-animate.window="animateChat()"
     @mouseenter="pause()"
     @mouseleave="resume()"
     @focusin="pause()"
     @focusout="resume()"
     class="hero-agent-preview relative bg-white dark:bg-neutral-950 flex flex-col h-[520px] sm:h-[580px] md:h-[640px]">

    {{-- Messages --}}
    <div x-ref="messagesScroll" class="flex-1 overflow-y-auto p-4 sm:p-6 md:px-8 md:py-6 space-y-5 sm:space-y-6 scroll-smooth">
        @include('home.partials.hero-agent-conversation')
    </div>

    {{-- Undo toast (anchored to chat panel, not scroll container) --}}
    <div class="mcp-el mcp-undo-toast pointer-events-none absolute bottom-20 left-1/2 -translate-x-1/2 z-20 inline-flex items-center gap-3 rounded-lg bg-gray-900 dark:bg-white px-3 py-2 text-xs font-medium text-white dark:text-gray-900 shadow-lg" aria-hidden="true">
        <x-ri-check-line class="w-3.5 h-3.5"/>
        <span>3 tasks marked complete</span>
        <button type="button" tabindex="-1" class="text-primary-300 dark:text-primary-700 font-semibold">Undo (5s)</button>
    </div>

    {{-- Input bar --}}
    <div class="mcp-el mcp-input border-t border-gray-100 dark:border-white/[0.06] px-4 sm:px-6 md:px-8 py-3">
        <div class="flex items-center gap-3 bg-gray-50/80 dark:bg-white/[0.03] rounded-lg border border-gray-200/80 dark:border-white/[0.06] px-3.5 py-2.5">
            <x-ri-sparkling-2-fill class="w-4 h-4 text-gray-400/50 dark:text-gray-500/50 shrink-0"/>
            <span class="text-sm text-gray-400 dark:text-gray-500 flex-1">Ask anything…</span>
            <x-ri-mic-line class="w-4 h-4 text-gray-300 dark:text-gray-600 shrink-0" aria-hidden="true"/>
            <div class="text-[10px] text-gray-300 dark:text-gray-600 border border-gray-200 dark:border-white/[0.06] rounded px-1.5 py-0.5 font-mono">⌘J</div>
        </div>
    </div>

</div>

<script>
    function heroChat() {
        return {
            ease: [0.22, 1, 0.36, 1],
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
