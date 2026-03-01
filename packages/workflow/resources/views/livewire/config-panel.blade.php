<div>
    @if($selectedNodeId)
        <div class="wf-panel-header">
            <h3>{{ $this->getPanelTitle() }}</h3>
            <button
                type="button"
                x-on:click="$dispatch('config-panel-close')"
                wire:click="deselectNode"
                class="wf-panel-close"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="wf-panel-body">
            {{-- Block Type Header --}}
            @php
                $color = $this->getCategoryColor();
                $colorClasses = match($color) {
                    'amber' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
                    'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
                    'green' => 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
                    'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
                    'orange' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
                    default => 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
                };
                $badgeClasses = match($color) {
                    'amber' => 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300',
                    'blue' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300',
                    'green' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300',
                    'purple' => 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300',
                    'orange' => 'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300',
                    default => 'bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-400',
                };
            @endphp
            <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                <div class="w-9 h-9 rounded-lg {{ $colorClasses }} flex items-center justify-center flex-shrink-0">
                    <x-dynamic-component :component="$this->getActionIcon()" class="w-5 h-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <span class="inline-block text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded {{ $badgeClasses }} mb-0.5">
                        {{ $this->getActionCategory() }}
                    </span>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 m-0 truncate">
                        {{ $this->getActionLabel() }}
                    </h3>
                </div>
            </div>

            <form wire:submit="saveConfig">
                {{ $this->form }}

                <div class="mt-4 flex gap-2">
                    <button type="submit" class="wf-save-btn text-[13px] px-4 py-1.5">
                        Save
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-slate-300 dark:text-slate-600"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 m-0 mb-1">No block selected</p>
            <span class="text-xs text-slate-400">Click a block on the canvas to configure it.</span>
        </div>
    @endif
</div>
