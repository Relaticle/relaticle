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
                  x-data="{
                      saving: false, saved: false, autoSaveTimer: null,
                      mentionOpen: false, mentionQuery: '', mentionTarget: null,
                      mentionPos: { top: 0, left: 0 }, mentionVars: [], mentionFiltered: [],
                      mentionIdx: 0, mentionAtPos: 0,
                      initMention() {
                          const graph = window.__wfGraph;
                          const meta = window.__wfMeta;
                          const nodeId = '{{ $selectedNodeId }}';
                          if (!graph || !nodeId) return;
                          const vars = [];
                          const visited = new Set();
                          const queue = [nodeId];
                          while (queue.length) {
                              const cid = queue.shift();
                              if (visited.has(cid)) continue;
                              visited.add(cid);
                              const cell = graph.getCellById(cid);
                              if (!cell || !cell.isNode()) continue;
                              if (cid !== nodeId) {
                                  const d = cell.getData() || {};
                                  let prefix = 'steps.' + cid + '.output';
                                  let src = d.label || d.actionType || d.type || 'Unknown';
                                  if (d.type === 'trigger') { prefix = 'trigger.record'; src = 'Trigger'; }
                                  const outputs = this._getOutputs(d, meta);
                                  outputs.forEach(o => vars.push({ key: prefix + '.' + o.key, label: o.label, source: src }));
                              }
                              (graph.getIncomingEdges(cell) || []).forEach(e => { const s = e.getSourceNode(); if (s) queue.push(s.id); });
                          }
                          vars.push({ key: 'now', label: 'Current Timestamp', source: 'Built-in' });
                          vars.push({ key: 'today', label: 'Today\'s Date', source: 'Built-in' });
                          this.mentionVars = vars;
                      },
                      _getOutputs(data, meta) {
                          if (data.type === 'trigger') {
                              const o = meta?.trigger_outputs?.[data.config?.event || 'manual'] || {};
                              return Object.entries(o).map(([k, v]) => ({ key: k, label: v.label }));
                          }
                          if (data.type === 'action' && data.actionType) {
                              const s = meta?.registered_actions?.[data.actionType]?.outputSchema || {};
                              return Object.entries(s).map(([k, v]) => ({ key: k, label: v.label }));
                          }
                          if (data.type === 'condition') return [{ key: 'result', label: 'Condition Result' }];
                          if (data.type === 'loop') return [{ key: 'item', label: 'Current Item' }, { key: 'index', label: 'Current Index' }];
                          return [];
                      },
                      onInputKeydown(e) {
                          if (this.mentionOpen) {
                              if (e.key === 'ArrowDown') { e.preventDefault(); this.mentionIdx = Math.min(this.mentionIdx + 1, this.mentionFiltered.length - 1); }
                              else if (e.key === 'ArrowUp') { e.preventDefault(); this.mentionIdx = Math.max(this.mentionIdx - 1, 0); }
                              else if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); if (this.mentionFiltered[this.mentionIdx]) this.insertMention(this.mentionFiltered[this.mentionIdx]); }
                              else if (e.key === 'Escape') { this.mentionOpen = false; }
                          }
                      },
                      onInputKey(e) {
                          const el = e.target;
                          if (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA') return;
                          const val = el.value;
                          const pos = el.selectionStart;
                          const before = val.substring(0, pos);
                          const atIdx = before.lastIndexOf('@');
                          if (atIdx === -1 || (atIdx > 0 && /\S/.test(before[atIdx - 1]))) { this.mentionOpen = false; return; }
                          const query = before.substring(atIdx + 1).toLowerCase();
                          if (query.includes(' ')) { this.mentionOpen = false; return; }
                          this.mentionQuery = query;
                          this.mentionAtPos = atIdx;
                          this.mentionTarget = el;
                          this.mentionIdx = 0;
                          this.mentionFiltered = this.mentionVars.filter(v =>
                              v.label.toLowerCase().includes(query) || v.key.toLowerCase().includes(query) || v.source.toLowerCase().includes(query)
                          ).slice(0, 8);
                          if (this.mentionFiltered.length === 0) { this.mentionOpen = false; return; }
                          const rect = el.getBoundingClientRect();
                          const panelRect = $el.closest('.wf-panel-body')?.getBoundingClientRect() || rect;
                          this.mentionPos = { top: rect.bottom - panelRect.top + 4, left: 0 };
                          this.mentionOpen = true;
                      },
                      insertMention(v) {
                          const el = this.mentionTarget;
                          if (!el) return;
                          const val = el.value;
                          const variable = '{{' + v.key + '}}';
                          const before = val.substring(0, this.mentionAtPos);
                          const after = val.substring(el.selectionStart);
                          el.value = before + variable + after;
                          const newPos = before.length + variable.length;
                          el.setSelectionRange(newPos, newPos);
                          el.dispatchEvent(new Event('input', { bubbles: true }));
                          el.dispatchEvent(new Event('change', { bubbles: true }));
                          const wireModel = el.getAttribute('wire:model') || el.getAttribute('wire:model.live') || el.getAttribute('wire:model.blur') || el.getAttribute('wire:model.defer');
                          if (wireModel) {
                              const lw = el.closest('[wire\\:id]');
                              if (lw) { const c = window.Livewire?.find(lw.getAttribute('wire:id')); if (c) c.$set(wireModel, el.value); }
                          }
                          this.mentionOpen = false;
                          el.focus();
                      }
                  }"
                  x-init="
                      initMention();
                      $el.addEventListener('change', () => {
                          clearTimeout(autoSaveTimer);
                          saving = true; saved = false;
                          autoSaveTimer = setTimeout(() => { $wire.saveConfig().then(() => { saving = false; saved = true; setTimeout(() => saved = false, 2000); }); }, 600);
                      });
                  "
                  @keydown="onInputKeydown($event)"
                  @input="onInputKey($event)"
            >
                {{-- Inputs section header --}}
                <h4 class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider m-0 mb-3">Inputs</h4>

                <div class="relative">
                    {{ $this->form }}

                    {{-- @ Mention Variable Dropdown --}}
                    <div x-show="mentionOpen" x-cloak
                         x-transition.opacity.duration.150ms
                         @click.outside="mentionOpen = false"
                         class="wf-var-picker"
                         :style="'top: ' + mentionPos.top + 'px; left: ' + mentionPos.left + 'px; position: absolute; z-index: 120;'"
                    >
                        <div class="px-2 pt-2 pb-1">
                            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Insert variable</span>
                        </div>
                        <template x-for="(v, i) in mentionFiltered" :key="v.key">
                            <button type="button"
                                    class="wf-var-item w-full"
                                    :class="{ 'bg-primary-50 dark:bg-primary-900/30': i === mentionIdx }"
                                    @click="insertMention(v)"
                                    @mouseenter="mentionIdx = i"
                            >
                                <span class="wf-var-key" x-text="v.key.split('.').pop()"></span>
                                <span class="wf-var-label truncate" x-text="v.label"></span>
                                <span class="text-[9px] text-slate-300 dark:text-slate-600 ml-auto flex-shrink-0" x-text="v.source"></span>
                            </button>
                        </template>
                        <div x-show="mentionFiltered.length === 0" class="px-3 py-2 text-xs text-slate-400">No matching variables</div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center gap-3">
                    <button type="submit" class="wf-btn-sm text-[13px] px-4 py-1.5">
                        Apply
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
