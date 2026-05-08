<x-filament-panels::page>
    <div
        x-data="dashboardChatInput(@js(\App\Filament\Pages\ChatConversation::getUrl()), @js(auth()->user()?->ai_preferences['default_model'] ?? 'auto'))"
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
            <div
                x-data="chatEditor({
                    initialDocument: { type: 'doc', content: [] },
                    placeholder: 'Ask anything...',
                    autofocus: true,
                    onSubmit: () => $root.dispatchEvent(new CustomEvent('dashboard:editor-submit', { bubbles: true })),
                    onChange: ({ document, text }) => {
                        $root.dispatchEvent(new CustomEvent('dashboard:editor-change', { bubbles: true, detail: { document, text } }));
                    },
                })"
                x-on:dashboard:editor-submit.window="submit()"
                x-on:dashboard:editor-change.window="input = $event.detail.text"
                {{-- No global setter needed — dashboardChatInput uses localEditor() to scope-resolve. --}}
                data-chat-context="dashboard"
                class="relative rounded-2xl border border-gray-200 bg-white transition focus-within:border-primary-500 dark:border-gray-700 dark:bg-gray-800"
            >
                <div x-ref="editor" class="relative"></div>

                <div class="flex items-center justify-end gap-2 px-3 pb-2">
                    @include('chat::livewire.chat.partials._model-picker')

                    <button
                        type="submit"
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-600 text-white transition hover:bg-primary-700 disabled:bg-primary-200 disabled:text-white dark:disabled:bg-primary-900/40 dark:disabled:text-primary-300"
                        :disabled="text.trim().length === 0 || submitting"
                        aria-label="Send message"
                    >
                        <x-heroicon-s-arrow-up class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div
                x-show="error"
                x-cloak
                role="alert"
                class="mt-2 text-xs text-red-600 dark:text-red-400"
                x-text="error"
            ></div>
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
                        @click="input = @js($prompt['prompt']); localEditor()?.setText(@js($prompt['prompt'])); $nextTick(() => submit())"
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
                            @click="input = @js($insight->prompt); localEditor()?.setText(@js($insight->prompt)); $nextTick(() => submit())"
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
            error: null,
            selectedModel: defaultModel || 'auto',
            modelOptions: [
                { value: 'auto', label: 'Auto', provider: null },
                { value: 'claude-sonnet', label: 'Sonnet 4.6', provider: 'anthropic' },
                { value: 'claude-opus', label: 'Opus 4.7', provider: 'anthropic' },
                { value: 'gpt-5-5', label: 'GPT 5.5', provider: 'openai' },
                { value: 'gpt-5-4', label: 'GPT 5.4', provider: 'openai' },
                { value: 'gemini-3-flash', label: 'Gemini 3 Flash', provider: 'gemini' },
                { value: 'gemini-3-1-pro', label: 'Gemini 3.1 Pro', provider: 'gemini' },
            ],
            providerIcons: @js([
                'anthropic' => svg('ri-claude-fill')->toHtml(),
                'openai' => svg('ri-openai-fill')->toHtml(),
                'gemini' => svg('ri-gemini-fill')->toHtml(),
            ]),

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

            modelProvider(value) {
                const found = this.modelOptions.find((o) => o.value === value);
                return found?.provider ?? null;
            },

            selectModel(value) {
                this.selectedModel = value;
            },

            // Scoped lookup of the dashboard's TipTap editor — avoids the
            // window.__dashboardEditor global which collides if any sibling
            // chat-interface instance also writes its own global.
            localEditor() {
                const root = this.$root || this.$el;
                if (! root) return null;
                const wrapper = root.querySelector('[x-data*="chatEditor"]');
                if (! wrapper || ! window.Alpine) return null;
                return window.Alpine.$data(wrapper);
            },

            async submit() {
                const editor = this.localEditor();
                if (!editor || editor.isEmpty() || this.submitting) return;

                this.submitting = true;
                this.error = null;

                const payload = editor.getDocument();

                try {
                    const res = await fetch(@js(route('chat.conversations.create')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({
                            document: payload,
                            model: this.selectedModel !== 'auto' ? this.selectedModel : undefined,
                        }),
                    });

                    if (!res.ok) {
                        this.submitting = false;
                        if (res.status === 422) {
                            const body = await res.json().catch(() => ({}));
                            this.error = body?.errors?.document?.[0] ?? 'Message is empty.';
                        } else if (res.status === 402) {
                            window.location.href = @js(url('/app/billing'));
                        } else {
                            this.error = 'Could not send. Try again.';
                        }
                        return;
                    }

                    const { conversation_id } = await res.json();
                    const target = chatUrl.replace(/\/+$/, '') + '/' + conversation_id;
                    window.location.href = target;
                } catch (_) {
                    this.submitting = false;
                    this.error = 'Network error. Try again.';
                }
            },
        }));
    </script>
    @endscript
</x-filament-panels::page>
