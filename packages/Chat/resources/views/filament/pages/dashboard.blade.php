<x-filament-panels::page>
    <div
        x-data="dashboardChatInput(@js(\App\Filament\Pages\ChatConversation::getUrl()), @js(auth()->user()?->ai_preferences['default_model'] ?? 'auto'))"
        x-on:keydown.escape.window="menuOpen = false"
        class="mx-auto max-w-2xl py-16"
    >
        {{-- Greeting --}}
        <div class="text-center">
            <h1 class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ $this->getGreeting() }}
            </h1>

            @if($recentChatId)
                <a
                    href="{{ \App\Filament\Pages\ChatConversation::getUrl(['conversationId' => $recentChatId]) }}"
                    class="mt-2 inline-flex items-center gap-1.5 text-sm text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                >
                    <x-heroicon-o-arrow-path class="h-3.5 w-3.5" />
                    <span>Recent chat &middot; {{ \Illuminate\Support\Str::limit($recentChatTitle ?? 'Untitled', 50) }}</span>
                </a>
            @endif
        </div>

        {{-- Chat input --}}
        <form @submit.prevent="submit()" class="mt-10">
            <div class="relative rounded-2xl border border-gray-200 bg-white transition focus-within:border-primary-500 dark:border-gray-700 dark:bg-gray-800">
                {{-- Mention pills --}}
                <div x-show="selectedMentions.length > 0" class="flex flex-wrap gap-1 px-3 pt-2">
                    <template x-for="mention in selectedMentions" :key="`${mention.type}-${mention.id}`">
                        <span class="inline-flex items-center gap-1 rounded-md bg-primary-100 px-1.5 py-0.5 text-xs text-primary-800 dark:bg-primary-900/30 dark:text-primary-200">
                            <span x-text="mention.label"></span>
                            <button
                                type="button"
                                @click="removeMention(mention)"
                                aria-label="Remove mention"
                                class="text-primary-500 hover:text-primary-700 dark:text-primary-300 dark:hover:text-primary-100"
                            >×</button>
                        </span>
                    </template>
                </div>

                <textarea
                    x-ref="chatInput"
                    x-init="$nextTick(() => $refs.chatInput?.focus())"
                    x-model="input"
                    @keydown.enter="if ($event.shiftKey) return; if (mention.open && mention.results.length > 0) { $event.preventDefault(); selectMention(mention.results[mention.activeIndex]); return; } $event.preventDefault(); submit()"
                    @keydown.escape="if (mention.open) { $event.preventDefault(); closeMention() }"
                    @keydown.arrow-up="if (mention.open && mention.results.length > 0) { $event.preventDefault(); mentionMoveActive(-1) }"
                    @keydown.arrow-down="if (mention.open && mention.results.length > 0) { $event.preventDefault(); mentionMoveActive(1) }"
                    @input="onTextareaInput($event)"
                    placeholder="Ask anything..."
                    rows="3"
                    autofocus
                    class="block w-full resize-none rounded-t-2xl border-0 bg-transparent px-4 pt-3 pb-2 text-sm leading-6 text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                    :disabled="submitting"
                ></textarea>

                {{-- Mention dropdown --}}
                <div
                    x-show="mention.open && mention.query.length >= 2"
                    x-cloak
                    @click.outside="closeMention()"
                    role="listbox"
                    aria-label="Mention suggestions"
                    class="absolute bottom-full left-0 right-8 z-50 mb-2 max-h-64 overflow-auto rounded-xl border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                    <div x-show="mention.fetching && mention.results.length === 0" class="px-3 py-3 text-center text-xs text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center gap-2">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-primary-500"></span>
                            Searching…
                        </span>
                    </div>

                    <div x-show="!mention.fetching && mention.error" class="px-3 py-3 text-center text-xs text-red-600 dark:text-red-400" role="alert">
                        <span x-text="mention.error"></span>
                        <button type="button" @click="fetchMentions(mention.query)" class="ml-2 underline">Retry</button>
                    </div>

                    <div x-show="!mention.fetching && !mention.error && mention.results.length === 0" class="px-3 py-3 text-center text-xs text-gray-500 dark:text-gray-400">
                        No matches for "<span x-text="mention.query"></span>".
                    </div>

                    <template x-if="!mention.fetching && !mention.error && mention.results.length > 0">
                        <div>
                            <template x-for="(item, idx) in mention.results" :key="`${item.type}-${item.id}`">
                                <button
                                    type="button"
                                    role="option"
                                    :aria-selected="idx === mention.activeIndex"
                                    @click="selectMention(item)"
                                    @mouseenter="mention.activeIndex = idx"
                                    :class="{ 'bg-primary-50 dark:bg-primary-900/30': idx === mention.activeIndex }"
                                    class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700"
                                >
                                    <span x-text="item.label" class="truncate"></span>
                                    <span x-text="mentionTypeLabel(item.type)" class="text-xs uppercase text-gray-400"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-2 px-3 pb-2">
                    {{-- Model picker --}}
                    <div class="relative">
                        <button
                            type="button"
                            x-on:click="menuOpen = !menuOpen"
                            class="inline-flex h-7 items-center gap-1 rounded-md border border-transparent bg-transparent px-2 text-xs font-medium text-gray-600 transition hover:border-gray-200 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:border-gray-700 dark:hover:bg-gray-700 dark:hover:text-white"
                            :aria-expanded="menuOpen"
                            aria-haspopup="listbox"
                            aria-label="Select AI model"
                        >
                            <span x-text="modelLabel(selectedModel)"></span>
                            <x-heroicon-o-chevron-up-down class="h-3 w-3" aria-hidden="true" />
                        </button>
                        <div
                            x-show="menuOpen"
                            x-on:click.away="menuOpen = false"
                            x-transition.opacity.duration.100ms
                            role="listbox"
                            aria-label="AI model options"
                            class="absolute bottom-full right-0 z-10 mb-2 w-56 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                            style="display: none;"
                        >
                            <template x-for="opt in modelOptions" :key="opt.value">
                                <button
                                    type="button"
                                    role="option"
                                    :aria-selected="selectedModel === opt.value"
                                    x-on:click="selectedModel = opt.value; menuOpen = false"
                                    class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700"
                                    :class="{ 'bg-gray-50 dark:bg-gray-700/50': selectedModel === opt.value }"
                                >
                                    <span
                                        x-html="providerIconHtml(opt.provider)"
                                        :class="providerIconColor(opt.provider) + ' inline-flex h-4 w-4 shrink-0 items-center justify-center'"
                                        aria-hidden="true"
                                    ></span>
                                    <span class="flex-1 truncate" x-text="opt.label"></span>
                                    <x-heroicon-s-check-circle
                                        x-show="selectedModel === opt.value"
                                        class="h-3.5 w-3.5 shrink-0 text-primary-600 dark:text-primary-400"
                                        aria-hidden="true"
                                    />
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Send --}}
                    <button
                        type="submit"
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-600 text-white transition hover:bg-primary-700 disabled:bg-primary-200 disabled:text-white dark:disabled:bg-primary-900/40 dark:disabled:text-primary-300"
                        :disabled="!input.trim() || submitting"
                        aria-label="Send message"
                    >
                        <x-heroicon-s-arrow-up class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </form>

        {{-- Suggested prompts --}}
        @php
            $suggestedPrompts = $this->getSuggestedPrompts();
        @endphp
        @if(!empty($suggestedPrompts))
            <div class="mt-3 flex flex-wrap justify-center gap-1.5">
                @foreach($suggestedPrompts as $prompt)
                    <button
                        type="button"
                        @click="input = @js($prompt['prompt']); $nextTick(() => submit())"
                        class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-700"
                    >
                        {{ $prompt['label'] }}
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Proactive insights --}}
        @php
            $insights = $this->getInsights();
        @endphp
        @if($insights->isNotEmpty())
            <div class="mt-14">
                <h2 class="mb-3 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Quick insights</h2>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($insights as $insight)
                        @php
                            $accentClasses = match ($insight->severity) {
                                'warning' => 'text-amber-600 dark:text-amber-400',
                                'success' => 'text-emerald-600 dark:text-emerald-400',
                                default => 'text-blue-600 dark:text-blue-400',
                            };
                        @endphp
                        <button
                            type="button"
                            @click="input = @js($insight->prompt); $nextTick(() => submit())"
                            class="group flex items-start gap-3 rounded-xl border border-gray-200 bg-white px-3.5 py-3 text-left transition hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600 dark:hover:bg-gray-700"
                        >
                            <span class="text-2xl font-semibold tabular-nums leading-none {{ $accentClasses }}">{{ $insight->count }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $insight->title }}</div>
                                <div class="mt-0.5 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">{{ $insight->description }}</div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @script
    <script>
        Alpine.data('dashboardChatInput', (chatUrl, defaultModel) => ({
            input: '',
            submitting: false,
            selectedModel: defaultModel || 'auto',
            menuOpen: false,
            selectedMentions: [],
            mention: {
                open: false,
                query: '',
                results: [],
                activeIndex: 0,
                triggerStart: -1,
                fetching: false,
                error: null,
                abort: null,
            },
            providerIcons: @js([
                'anthropic' => svg('ri-claude-fill')->toHtml(),
                'openai' => svg('ri-openai-fill')->toHtml(),
                'gemini' => svg('ri-gemini-fill')->toHtml(),
            ]),
            modelOptions: [
                { value: 'auto', label: 'Auto', provider: null },
                { value: 'claude-sonnet', label: 'Sonnet 4.6', provider: 'anthropic' },
                { value: 'claude-opus', label: 'Opus 4.7', provider: 'anthropic' },
                { value: 'gpt-5-5', label: 'GPT 5.5', provider: 'openai' },
                { value: 'gpt-5-4', label: 'GPT 5.4', provider: 'openai' },
                { value: 'gemini-3-flash', label: 'Gemini 3 Flash', provider: 'gemini' },
                { value: 'gemini-3-1-pro', label: 'Gemini 3.1 Pro', provider: 'gemini' },
            ],

            providerIconHtml(provider) {
                return provider ? (this.providerIcons[provider] || '') : '';
            },

            providerIconColor(provider) {
                return ({
                    anthropic: 'text-[#D4763C]',
                    openai: 'text-gray-900 dark:text-gray-200',
                    gemini: 'text-blue-500',
                })[provider] || '';
            },

            modelLabel(value) {
                const found = this.modelOptions.find((o) => o.value === value);
                return (found || this.modelOptions[0]).label;
            },

            mentionTypeLabel(type) {
                return ({ company: 'Company', people: 'Person', opportunity: 'Deal', task: 'Task', note: 'Note' })[type] || type;
            },

            onTextareaInput(event) {
                this.input = event.target.value;
                this.detectMentionTrigger(event.target);
            },

            detectMentionTrigger(textarea) {
                const cursor = textarea.selectionStart ?? this.input.length;
                let text = this.input.slice(0, cursor);

                const tokens = this.selectedMentions
                    .map((m) => m.token)
                    .filter((t) => typeof t === 'string' && t.length > 0)
                    .sort((a, b) => b.length - a.length);
                for (const token of tokens) {
                    const escaped = token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    text = text.replace(new RegExp(escaped, 'g'), (m) => '\0'.repeat(m.length));
                }

                const match = text.match(/(?:^|\s)@([\p{L}\p{N}_-]+(?:\s[\p{L}\p{N}_-]+)*)?$/iu);

                if (!match) {
                    this.closeMention();
                    return;
                }

                this.mention.open = true;
                this.mention.query = match[1] ?? '';
                this.mention.triggerStart = cursor - this.mention.query.length - 1;

                if (this.mention.query.length >= 2) {
                    this.fetchMentions(this.mention.query);
                } else {
                    this.mention.abort?.abort();
                    this.mention.abort = null;
                    this.mention.results = [];
                    this.mention.activeIndex = 0;
                }
            },

            async fetchMentions(query) {
                if (this.mention.abort) this.mention.abort.abort();
                this.mention.abort = new AbortController();
                this.mention.fetching = true;
                this.mention.error = null;
                try {
                    const res = await fetch('/chat/mentions?q=' + encodeURIComponent(query), {
                        method: 'GET',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: this.mention.abort.signal,
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        this.mention.results = [];
                        this.mention.activeIndex = 0;
                        this.mention.error = "Couldn't load suggestions.";
                        return;
                    }
                    const body = await res.json();
                    this.mention.results = (body.data || []).map((item) => ({ type: item.type, id: item.id, label: item.name }));
                    this.mention.activeIndex = 0;
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        this.mention.results = [];
                        this.mention.activeIndex = 0;
                        this.mention.error = "Couldn't load suggestions.";
                    }
                } finally {
                    this.mention.fetching = false;
                }
            },

            selectMention(item) {
                if (!item) return;
                const before = this.input.slice(0, this.mention.triggerStart);
                const afterCursor = this.input.slice(this.mention.triggerStart + 1 + this.mention.query.length);
                const token = `@${item.label.replace(/\s+/g, '_')}`;
                const newInput = `${before}${token} ${afterCursor}`;
                this.input = newInput;
                this.closeMention();
                this.selectedMentions.push({ id: item.id, type: item.type, label: item.label, token });
                this.$nextTick(() => {
                    const ta = this.$refs.chatInput;
                    if (ta) {
                        ta.value = newInput;
                        const pos = (before + token + ' ').length;
                        ta.focus();
                        ta.setSelectionRange(pos, pos);
                    }
                });
            },

            closeMention() {
                if (this.mention.abort) this.mention.abort.abort();
                this.mention = { open: false, query: '', results: [], activeIndex: 0, triggerStart: -1, fetching: false, error: null, abort: null };
            },

            removeMention(mention) {
                this.selectedMentions = this.selectedMentions.filter((m) => !(m.type === mention.type && m.id === mention.id));
                const escaped = mention.token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                this.input = this.input
                    .replace(new RegExp(escaped + '\\s?', 'g'), '')
                    .replace(/\s{2,}/g, ' ')
                    .trimStart();
            },

            mentionMoveActive(delta) {
                if (!this.mention.open || this.mention.results.length === 0) return;
                const len = this.mention.results.length;
                this.mention.activeIndex = (this.mention.activeIndex + delta + len) % len;
            },

            submit() {
                const text = this.input.trim();
                if (!text || this.submitting) return;
                this.submitting = true;

                if (this.selectedMentions.length > 0) {
                    try {
                        const payload = this.selectedMentions.map((m) => ({
                            id: m.id,
                            type: m.type,
                            label: m.label,
                            token: m.token,
                        }));
                        localStorage.setItem('chat:mentions', JSON.stringify(payload));
                    } catch (_) { /* localStorage unavailable, fall through */ }
                }

                const url = new URL(chatUrl, window.location.origin);
                url.searchParams.set('message', text);
                if (this.selectedModel && this.selectedModel !== 'auto') {
                    url.searchParams.set('model', this.selectedModel);
                }

                window.location.href = url.toString();
            },
        }));
    </script>
    @endscript
</x-filament-panels::page>
