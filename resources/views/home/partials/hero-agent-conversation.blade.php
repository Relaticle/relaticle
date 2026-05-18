{{-- Exchange 1: read overdue tasks --}}
<div class="mcp-el mcp-user mcp-user-1 flex items-start gap-2.5">
    <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-white/[0.1] flex items-center justify-center shrink-0 mt-4">
        <x-ri-user-3-fill class="w-3 h-3 text-gray-500 dark:text-gray-400"/>
    </div>
    <div class="flex-1 min-w-0">
        <div class="text-xs font-semibold text-gray-900 dark:text-white mb-1">You</div>
        <div class="text-sm text-gray-900 dark:text-gray-100 leading-relaxed">
            What's overdue this week?
        </div>
    </div>
</div>

<div class="flex items-start gap-2.5">
    <div class="w-6 h-6 rounded-full bg-gray-900 dark:bg-white/[0.15] flex items-center justify-center shrink-0 mt-4 mcp-el mcp-avatar mcp-avatar-1">
        <x-ri-sparkling-2-fill class="w-3 h-3 text-white dark:text-gray-300"/>
    </div>
    <div class="flex-1 min-w-0">
        <div class="mcp-el mcp-label mcp-label-1 text-xs font-semibold text-gray-900 dark:text-white mb-1">Assistant</div>
        <div class="space-y-2.5">
            <div class="mcp-el mcp-tool mcp-tool-1 flex items-center gap-2 text-[11px] sm:text-xs">
                <span class="inline-flex items-center gap-1.5 text-primary dark:text-primary-300 font-medium">
                    <x-ri-loader-4-line class="w-3 h-3 shrink-0"/>
                    <span>Searching tasks…</span>
                </span>
                <span class="text-emerald-600 dark:text-emerald-400 font-medium">done</span>
            </div>
            <div class="mcp-el mcp-text mcp-text-1 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                You have 3 overdue tasks:
            </div>
            <div class="mcp-el mcp-task-card mcp-task-1 rounded-lg p-3 border border-gray-200/80 dark:border-white/[0.06] bg-gray-50/80 dark:bg-white/[0.02]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <x-ri-checkbox-blank-circle-line class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 shrink-0"/>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Call Sarah Chen</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Due yesterday · Kovra Systems</div>
                        </div>
                    </div>
                    <span class="text-[10px] font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider">Overdue</span>
                </div>
            </div>
            <div class="mcp-el mcp-task-card mcp-task-2 rounded-lg p-3 border border-gray-200/80 dark:border-white/[0.06] bg-gray-50/80 dark:bg-white/[0.02]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <x-ri-checkbox-blank-circle-line class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 shrink-0"/>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Send proposal to Trellis Labs</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Due 2 days ago · Trellis Labs</div>
                        </div>
                    </div>
                    <span class="text-[10px] font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider">Overdue</span>
                </div>
            </div>
            <div class="mcp-el mcp-task-card mcp-task-3 rounded-lg p-3 border border-gray-200/80 dark:border-white/[0.06] bg-gray-50/80 dark:bg-white/[0.02]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <x-ri-checkbox-blank-circle-line class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 shrink-0"/>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Schedule demo with Kovra Systems</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Due 3 days ago · Kovra Systems</div>
                        </div>
                    </div>
                    <span class="text-[10px] font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider">Overdue</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Exchange 2: destructive op gated by approval (climax) --}}
<div class="mcp-el mcp-user mcp-user-2 flex items-start gap-2.5">
    <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-white/[0.1] flex items-center justify-center shrink-0 mt-4">
        <x-ri-user-3-fill class="w-3 h-3 text-gray-500 dark:text-gray-400"/>
    </div>
    <div class="flex-1 min-w-0">
        <div class="text-xs font-semibold text-gray-900 dark:text-white mb-1">You</div>
        <div class="text-sm text-gray-900 dark:text-gray-100 leading-relaxed">
            Mark them all as done.
        </div>
    </div>
</div>

<div class="flex items-start gap-2.5">
    <div class="w-6 h-6 rounded-full bg-gray-900 dark:bg-white/[0.15] flex items-center justify-center shrink-0 mt-4 mcp-el mcp-avatar mcp-avatar-2">
        <x-ri-sparkling-2-fill class="w-3 h-3 text-white dark:text-gray-300"/>
    </div>
    <div class="flex-1 min-w-0">
        <div class="mcp-el mcp-label mcp-label-2 text-xs font-semibold text-gray-900 dark:text-white mb-1">Assistant</div>
        <div class="space-y-2.5">
            <div class="mcp-el mcp-text mcp-text-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                I'll mark 3 tasks complete. Confirm to proceed.
            </div>
            <div class="mcp-el mcp-action-card rounded-lg border border-amber-200/80 dark:border-amber-500/30 bg-amber-50/60 dark:bg-amber-500/[0.06] overflow-hidden" aria-hidden="true">
                <div class="px-3 pt-2.5 pb-2 flex items-center gap-2">
                    <x-ri-shield-keyhole-line class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0"/>
                    <span class="text-[11px] uppercase tracking-wider font-semibold text-amber-700 dark:text-amber-400">Approval required</span>
                </div>
                <div class="px-3 pb-3">
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">Mark 3 tasks complete</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Call Sarah Chen · Send proposal · Schedule demo</div>
                    <div class="mt-3 flex items-center gap-2">
                        <button type="button" tabindex="-1" class="inline-flex items-center gap-1.5 rounded-md bg-gray-900 dark:bg-white px-3 py-1.5 text-[11px] font-medium text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors">
                            <x-ri-check-line class="w-3 h-3"/>
                            Approve
                        </button>
                        <button type="button" tabindex="-1" class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 dark:border-white/[0.08] bg-white dark:bg-white/[0.03] px-3 py-1.5 text-[11px] font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/[0.06] transition-colors">
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

{{-- Exchange 3: create with @-mention --}}
<div class="mcp-el mcp-user mcp-user-3 flex items-start gap-2.5">
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

<div class="flex items-start gap-2.5">
    <div class="w-6 h-6 rounded-full bg-gray-900 dark:bg-white/[0.15] flex items-center justify-center shrink-0 mt-4 mcp-el mcp-avatar mcp-avatar-3">
        <x-ri-sparkling-2-fill class="w-3 h-3 text-white dark:text-gray-300"/>
    </div>
    <div class="flex-1 min-w-0">
        <div class="mcp-el mcp-label mcp-label-3 text-xs font-semibold text-gray-900 dark:text-white mb-1">Assistant</div>
        <div class="space-y-2.5">
            <div class="mcp-el mcp-tool mcp-tool-3 flex items-center gap-2 text-[11px] sm:text-xs">
                <span class="inline-flex items-center gap-1.5 text-primary dark:text-primary-300 font-medium">
                    <x-ri-loader-4-line class="w-3 h-3 shrink-0"/>
                    <span>Creating contact…</span>
                </span>
                <span class="text-emerald-600 dark:text-emerald-400 font-medium">done</span>
            </div>
            <div class="mcp-el mcp-text mcp-text-3 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                Added Sarah and linked her to Kovra Systems.
            </div>
            <div class="mcp-el mcp-card rounded-lg p-3 border border-gray-200/80 dark:border-white/[0.06] bg-gray-50/80 dark:bg-white/[0.02]">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">Sarah Chen</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">VP of Engineering · Kovra Systems</div>
                    </div>
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-rose-400 to-orange-300 dark:from-rose-500 dark:to-orange-400 flex items-center justify-center shrink-0">
                        <span class="text-[10px] font-bold text-white">SC</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
