{{-- Exchange 1: read overdue tasks --}}
{{-- User msg: right-aligned purple pill --}}
<div class="mcp-el mcp-user mcp-user-1 flex justify-end">
    <div class="max-w-[80%] rounded-2xl rounded-br-md bg-primary-600 px-4 py-3 text-sm text-white">
        What's overdue this week?
    </div>
</div>

{{-- Assistant msg: left-aligned white card --}}
<div class="flex flex-col items-start">
    <div class="mcp-el mcp-avatar mcp-avatar-1 max-w-[85%] rounded-2xl rounded-bl-md bg-white px-4 py-3 text-sm text-gray-900 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/[0.06]">
        {{-- Empty label spacer so existing animation target stays valid --}}
        <span class="mcp-el mcp-label mcp-label-1 sr-only">Assistant</span>

        <div class="mcp-el mcp-tool mcp-tool-1 flex items-center gap-2 text-micro">
            <span class="h-1.5 w-1.5 rounded-full bg-gray-400 motion-safe:animate-pulse dark:bg-gray-500" aria-hidden="true"></span>
            <span class="font-medium text-gray-600 dark:text-gray-300">Searching tasks…</span>
            <span class="text-emerald-600 dark:text-emerald-400 font-medium">done</span>
        </div>

        <div class="mcp-el mcp-text mcp-text-1 mt-2 leading-relaxed text-gray-700 dark:text-gray-200">
            You have 3 overdue tasks:
        </div>

        <div class="mt-3 space-y-2">
            <div class="mcp-el mcp-task-card mcp-task-1 rounded-xl border border-gray-200 bg-white p-3 dark:border-white/[0.06] dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <x-heroicon-o-stop-circle class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 shrink-0"/>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Call Sarah Chen</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Due yesterday · Kovra Systems</div>
                        </div>
                    </div>
                    <span class="text-pico font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider">Overdue</span>
                </div>
            </div>
            <div class="mcp-el mcp-task-card mcp-task-2 rounded-xl border border-gray-200 bg-white p-3 dark:border-white/[0.06] dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <x-heroicon-o-stop-circle class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 shrink-0"/>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Send proposal to Trellis Labs</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Due 2 days ago · Trellis Labs</div>
                        </div>
                    </div>
                    <span class="text-pico font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider">Overdue</span>
                </div>
            </div>
            <div class="mcp-el mcp-task-card mcp-task-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-white/[0.06] dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <x-heroicon-o-stop-circle class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 shrink-0"/>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Schedule demo with Kovra Systems</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Due 3 days ago · Kovra Systems</div>
                        </div>
                    </div>
                    <span class="text-pico font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider">Overdue</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Exchange 2: destructive op gated by approval (climax) --}}
<div class="mcp-el mcp-user mcp-user-2 flex justify-end">
    <div class="max-w-[80%] rounded-2xl rounded-br-md bg-primary-600 px-4 py-3 text-sm text-white">
        Mark them all as done.
    </div>
</div>

<div class="flex flex-col items-start">
    <div class="mcp-el mcp-avatar mcp-avatar-2 max-w-[85%] rounded-2xl rounded-bl-md bg-white px-4 py-3 text-sm text-gray-900 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/[0.06]">
        <span class="mcp-el mcp-label mcp-label-2 sr-only">Assistant</span>

        <div class="mcp-el mcp-text mcp-text-2 leading-relaxed text-gray-700 dark:text-gray-200">
            I'll mark 3 tasks complete. Confirm to proceed.
        </div>

        <div class="mcp-el mcp-action-card mt-3 overflow-hidden rounded-xl border border-amber-200 bg-amber-50/60 dark:border-amber-500/30 dark:bg-amber-500/[0.06]" aria-hidden="true">
            <div class="flex items-center gap-2 px-3 pt-2.5 pb-2">
                <x-heroicon-o-shield-check class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0"/>
                <span class="text-micro uppercase tracking-wider font-semibold text-amber-700 dark:text-amber-400">Approval required</span>
            </div>
            <div class="px-3 pb-3">
                <div class="text-sm font-semibold text-gray-900 dark:text-white">Mark 3 tasks complete</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Call Sarah Chen · Send proposal · Schedule demo</div>
                <div class="mt-3 flex items-center gap-2">
                    <button type="button" tabindex="-1" class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-micro font-medium text-white">
                        <x-heroicon-o-check class="w-3 h-3"/>
                        Approve
                    </button>
                    <button type="button" tabindex="-1" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-1.5 text-micro font-medium text-white">
                        <x-heroicon-o-x-mark class="w-3 h-3"/>
                        Reject
                    </button>
                    <span class="ml-auto text-pico text-gray-400 dark:text-gray-500">Undo for 5s after approval</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Exchange 3: create with @-mention --}}
<div class="mcp-el mcp-user mcp-user-3 flex justify-end">
    <div class="max-w-[80%] rounded-2xl rounded-br-md bg-primary-600 px-4 py-3 text-sm leading-relaxed text-white">
        Add Sarah Chen as a contact at <span class="inline-flex items-center rounded-md bg-primary-100 px-1.5 py-0.5 text-xs font-medium text-primary-800 align-baseline dark:bg-primary-900/30 dark:text-primary-200">@Kovra Systems</span>. She's VP of Engineering.
    </div>
</div>

<div class="flex flex-col items-start">
    <div class="mcp-el mcp-avatar mcp-avatar-3 max-w-[85%] rounded-2xl rounded-bl-md bg-white px-4 py-3 text-sm text-gray-900 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/[0.06]">
        <span class="mcp-el mcp-label mcp-label-3 sr-only">Assistant</span>

        <div class="mcp-el mcp-tool mcp-tool-3 flex items-center gap-2 text-micro">
            <span class="h-1.5 w-1.5 rounded-full bg-gray-400 motion-safe:animate-pulse dark:bg-gray-500" aria-hidden="true"></span>
            <span class="font-medium text-gray-600 dark:text-gray-300">Creating contact…</span>
            <span class="text-emerald-600 dark:text-emerald-400 font-medium">done</span>
        </div>

        <div class="mcp-el mcp-text mcp-text-3 mt-2 leading-relaxed text-gray-700 dark:text-gray-200">
            Added Sarah and linked her to Kovra Systems.
        </div>

        <div class="mcp-el mcp-card mt-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-white/[0.06] dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">Sarah Chen</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">VP of Engineering · Kovra Systems</div>
                </div>
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-rose-400 to-orange-300 dark:from-rose-500 dark:to-orange-400 shrink-0">
                    <span class="text-pico font-bold text-white">SC</span>
                </div>
            </div>
        </div>
    </div>
</div>
