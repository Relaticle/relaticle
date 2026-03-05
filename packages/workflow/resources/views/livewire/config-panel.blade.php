<div>
    @if($selectedNodeId)
        @php
            $color = $this->getCategoryColor();
            $colorClasses = match($color) {
                'amber' => 'bg-amber-500',
                'blue' => 'bg-blue-500',
                'green' => 'bg-green-500',
                'purple' => 'bg-purple-500',
                'orange' => 'bg-orange-500',
                'red' => 'bg-red-500',
                'sky' => 'bg-sky-500',
                'gray' => 'bg-gray-400',
                default => 'bg-gray-400',
            };
            $categoryTextClasses = match($color) {
                'amber' => 'text-amber-600 dark:text-amber-400',
                'blue' => 'text-blue-600 dark:text-blue-400',
                'green' => 'text-green-600 dark:text-green-400',
                'purple' => 'text-purple-600 dark:text-purple-400',
                'orange' => 'text-orange-600 dark:text-orange-400',
                'red' => 'text-red-600 dark:text-red-400',
                'sky' => 'text-sky-600 dark:text-sky-400',
                'gray' => 'text-gray-500 dark:text-gray-400',
                default => 'text-gray-500 dark:text-gray-400',
            };
        @endphp

        {{-- Block identity header (Attio-style: icon + category + name + close) --}}
        <div class="flex items-start gap-3 px-4 pt-4 pb-3 border-b border-gray-100 dark:border-gray-700/60">
            <div class="w-8 h-8 rounded-lg {{ $colorClasses }} flex items-center justify-center flex-shrink-0 mt-0.5">
                <x-dynamic-component :component="$this->getActionIcon()" class="w-4 h-4 text-white" />
            </div>
            <div class="min-w-0 flex-1">
                <span class="block text-[10px] font-semibold uppercase tracking-wider {{ $categoryTextClasses }} mb-0.5">
                    {{ $this->getActionCategory() }}
                </span>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 m-0 leading-snug truncate">
                    {{ $this->getActionLabel() }}
                </h3>
            </div>
            <button
                type="button"
                x-on:click="$dispatch('config-panel-close')"
                wire:click="deselectNode"
                class="wf-panel-close flex-shrink-0"
                title="Close"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="wf-panel-body">
            <form wire:submit="saveConfig"
                  x-data="{ saving: false, saved: false, autoSaveTimer: null }"
                  x-init="
                      $el.addEventListener('change', () => {
                          clearTimeout(autoSaveTimer);
                          saving = true; saved = false;
                          autoSaveTimer = setTimeout(() => { $wire.saveConfig().then(() => { saving = false; saved = true; setTimeout(() => saved = false, 2000); }); }, 600);
                      });
                  "
            >
                {{-- Inputs section header --}}
                <h4 class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider m-0 mb-3">Inputs</h4>

                {{ $this->form }}

                <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center gap-3">
                    <button type="submit" class="wf-publish-btn text-[13px] px-5 py-2">
                        Save
                    </button>
                    <span x-show="saving" x-cloak class="text-xs text-slate-400 flex items-center gap-1">
                        <svg class="animate-spin w-3 h-3" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4 31.4" stroke-linecap="round"/></svg>
                        Saving...
                    </span>
                    <span x-show="saved" x-cloak x-transition.opacity.duration.300ms class="text-xs text-green-500 flex items-center gap-1">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Saved
                    </span>
                </div>
            </form>
        </div>
    @else
        <div class="wf-panel-header">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Block Settings</h3>
            <button type="button" x-on:click="closePanel()" class="wf-panel-close" title="Close panel">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="flex flex-col items-center justify-center py-12 px-4 text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-slate-300 dark:text-slate-600"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 m-0 mb-1">No block selected</p>
            <span class="text-xs text-slate-400 text-center">Click any block on the canvas to configure its settings.</span>
        </div>
    @endif

    <x-filament-actions::modals />
</div>
