<style>.hero-agent-preview .mcp-el { opacity: 0; }</style>

<div x-data="heroChat()"
     @hero-chat-reset.window="resetChat()"
     @hero-chat-animate.window="animateChat()"
     class="hero-agent-preview bg-white dark:bg-neutral-950 flex flex-col min-h-[300px] sm:min-h-[400px] md:min-h-[500px]">

    {{-- Messages --}}
    <div class="flex-1 p-4 sm:p-6 md:px-8 md:py-6 space-y-5 sm:space-y-6">

        {{-- User 1 --}}
        <div class="mcp-el mcp-user flex items-start gap-2.5">
            <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-white/[0.1] flex items-center justify-center shrink-0 mt-4">
                <x-ri-user-3-fill class="w-3 h-3 text-gray-500 dark:text-gray-400"/>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-semibold text-gray-900 dark:text-white mb-1">You</div>
                <div class="text-sm text-gray-900 dark:text-gray-100 leading-relaxed">
                    Add Sarah Chen as a contact at <span class="inline-flex items-center gap-1 rounded-md bg-primary/10 dark:bg-primary/20 px-1.5 py-0.5 text-[12.5px] font-medium text-primary-700 dark:text-primary-300 align-baseline">@Kovra Systems</span>. She's VP of Engineering.
                </div>
            </div>
        </div>

        {{-- Assistant 1: Tool call + result card --}}
        <div class="flex items-start gap-2.5">
            <div class="w-6 h-6 rounded-full bg-gray-900 dark:bg-white/[0.15] flex items-center justify-center shrink-0 mt-4 mcp-el mcp-avatar">
                <x-ri-sparkling-2-fill class="w-3 h-3 text-white dark:text-gray-300"/>
            </div>
            <div class="flex-1 min-w-0">
                <div class="mcp-el mcp-label text-xs font-semibold text-gray-900 dark:text-white mb-1">Assistant</div>
                <div class="space-y-2.5">
                    <div class="mcp-el mcp-tool flex items-center gap-2 text-[11px] sm:text-xs">
                        <span class="inline-flex items-center gap-1.5 text-primary dark:text-primary-300 font-medium">
                            <x-ri-loader-4-line class="w-3 h-3 shrink-0"/>
                            <span>Creating contact…</span>
                        </span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">done</span>
                    </div>
                    <div class="mcp-el mcp-text text-sm text-gray-600 dark:text-gray-300 leading-relaxed">Added Sarah and linked her to Kovra Systems.</div>
                    <a href="#" class="mcp-el mcp-card block rounded-lg p-3 border border-gray-200/80 dark:border-white/[0.06] bg-gray-50/80 dark:bg-white/[0.02] hover:border-gray-300 dark:hover:border-white/[0.10] transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">Sarah Chen</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">VP of Engineering · Kovra Systems</div>
                            </div>
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-rose-400 to-orange-300 dark:from-rose-500 dark:to-orange-400 flex items-center justify-center shrink-0">
                                <span class="text-[10px] font-bold text-white">SC</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        {{-- User 2 --}}
        <div class="mcp-el mcp-user flex items-start gap-2.5">
            <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-white/[0.1] flex items-center justify-center shrink-0 mt-4">
                <x-ri-user-3-fill class="w-3 h-3 text-gray-500 dark:text-gray-400"/>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-semibold text-gray-900 dark:text-white mb-1">You</div>
                <div class="text-sm text-gray-900 dark:text-gray-100 leading-relaxed">
                    Delete the <span class="inline-flex items-center gap-1 rounded-md bg-primary/10 dark:bg-primary/20 px-1.5 py-0.5 text-[12.5px] font-medium text-primary-700 dark:text-primary-300 align-baseline">@Trellis Labs</span> opportunity.
                </div>
            </div>
        </div>

        {{-- Assistant 2: Approval card (destructive op gate) --}}
        <div class="flex items-start gap-2.5">
            <div class="w-6 h-6 rounded-full bg-gray-900 dark:bg-white/[0.15] flex items-center justify-center shrink-0 mt-4 mcp-el mcp-avatar">
                <x-ri-sparkling-2-fill class="w-3 h-3 text-white dark:text-gray-300"/>
            </div>
            <div class="flex-1 min-w-0">
                <div class="mcp-el mcp-label text-xs font-semibold text-gray-900 dark:text-white mb-1">Assistant</div>
                <div class="space-y-2.5">
                    <div class="mcp-el mcp-text text-sm text-gray-600 dark:text-gray-300 leading-relaxed">This will delete an opportunity. Confirm to proceed.</div>
                    <div class="mcp-el mcp-action-card rounded-lg border border-amber-200/80 dark:border-amber-500/30 bg-amber-50/60 dark:bg-amber-500/[0.06] overflow-hidden">
                        <div class="px-3 pt-2.5 pb-2 flex items-center gap-2">
                            <x-ri-shield-keyhole-line class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0"/>
                            <span class="text-[11px] uppercase tracking-wider font-semibold text-amber-700 dark:text-amber-400">Approval required</span>
                        </div>
                        <div class="px-3 pb-3">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">Delete opportunity</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Trellis Labs · Team Expansion · $85K</div>
                            <div class="mt-3 flex items-center gap-2">
                                <button type="button" class="inline-flex items-center gap-1.5 rounded-md bg-gray-900 dark:bg-white px-3 py-1.5 text-[11px] font-medium text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors">
                                    <x-ri-check-line class="w-3 h-3"/>
                                    Approve
                                </button>
                                <button type="button" class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 dark:border-white/[0.08] bg-white dark:bg-white/[0.03] px-3 py-1.5 text-[11px] font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/[0.06] transition-colors">
                                    <x-ri-close-line class="w-3 h-3"/>
                                    Reject
                                </button>
                                <span class="ml-auto text-[10px] text-gray-400 dark:text-gray-500">Undo for 5s after approval</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

            resetChat() {
                this.$root.querySelectorAll('.mcp-el').forEach(function(el) {
                    el.style.opacity = '0';
                });
            },

            animateChat() {
                this.resetChat();

                if (typeof animate !== 'function') return;

                var root = this.$root;
                var ease = this.ease;
                var users = root.querySelectorAll('.mcp-user');
                var avatars = root.querySelectorAll('.mcp-avatar');
                var labels = root.querySelectorAll('.mcp-label');
                var tools = root.querySelectorAll('.mcp-tool');
                var texts = root.querySelectorAll('.mcp-text');

                animate(root.querySelector('.mcp-input'), { opacity: [0, 1] }, { duration: 0.3, ease: ease });

                // Conversation 1 — non-destructive create with @-mention
                animate(users[0], { opacity: [0, 1], transform: ['translateX(12px)', 'translateX(0px)'] }, { delay: 0.2, duration: 0.4, ease: ease });
                animate(avatars[0], { opacity: [0, 1], transform: ['scale(0.8)', 'scale(1)'] }, { delay: 0.65, duration: 0.3, ease: ease });
                animate(labels[0], { opacity: [0, 1] }, { delay: 0.65, duration: 0.3, ease: ease });
                animate(tools[0], { opacity: [0, 1], transform: ['translateX(-6px)', 'translateX(0px)'] }, { delay: 0.7, duration: 0.3, ease: ease });
                animate(texts[0], { opacity: [0, 1], transform: ['translateY(6px)', 'translateY(0px)'] }, { delay: 0.95, duration: 0.35, ease: ease });
                animate(root.querySelector('.mcp-card'), { opacity: [0, 1], transform: ['scale(0.97)', 'scale(1)'] }, { delay: 1.15, duration: 0.4, ease: ease });

                // Conversation 2 — destructive op gated by approval
                animate(users[1], { opacity: [0, 1], transform: ['translateX(12px)', 'translateX(0px)'] }, { delay: 1.6, duration: 0.4, ease: ease });
                animate(avatars[1], { opacity: [0, 1], transform: ['scale(0.8)', 'scale(1)'] }, { delay: 2.0, duration: 0.3, ease: ease });
                animate(labels[1], { opacity: [0, 1] }, { delay: 2.0, duration: 0.3, ease: ease });
                animate(texts[1], { opacity: [0, 1], transform: ['translateY(6px)', 'translateY(0px)'] }, { delay: 2.2, duration: 0.35, ease: ease });
                animate(root.querySelector('.mcp-action-card'), { opacity: [0, 1], transform: ['translateY(8px) scale(0.98)', 'translateY(0px) scale(1)'] }, { delay: 2.45, duration: 0.45, ease: ease });
            }
        };
    }
</script>
