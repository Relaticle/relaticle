<x-filament-panels::page>
    <div
        x-data="{
            message: '',
            submitting: false,
            selectedModel: @js(auth()->user()?->ai_preferences['default_model'] ?? 'auto'),
            menuOpen: false,
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

            submit() {
                const text = this.message.trim();
                if (!text || this.submitting) return;
                this.submitting = true;

                const url = new URL(@js(\App\Filament\Pages\ChatConversation::getUrl()), window.location.origin);
                url.searchParams.set('message', text);
                if (this.selectedModel && this.selectedModel !== 'auto') {
                    url.searchParams.set('model', this.selectedModel);
                }

                window.location.href = url.toString();
            }
        }"
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
            <div class="rounded-2xl border border-gray-200 bg-white transition focus-within:border-primary-500 dark:border-gray-700 dark:bg-gray-800">
                <textarea
                    x-model="message"
                    @keydown.enter.prevent="if(!$event.shiftKey) submit()"
                    placeholder="Ask anything..."
                    rows="3"
                    class="block w-full resize-none rounded-t-2xl border-0 bg-transparent px-4 pt-3 pb-2 text-sm leading-6 text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                    :disabled="submitting"
                ></textarea>
                <div class="flex items-center justify-end gap-2 px-3 pb-2">
                    {{-- Model picker --}}
                    <div class="relative">
                        <button
                            type="button"
                            x-on:click="menuOpen = !menuOpen"
                            class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
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

                    {{-- Slash-command hint --}}
                    <kbd
                        class="hidden h-6 select-none items-center justify-center rounded-md border border-gray-200 bg-gray-50 px-1.5 font-mono text-[11px] text-gray-500 sm:inline-flex dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400"
                        title="Type / for shortcuts"
                        aria-hidden="true"
                    >/</kbd>

                    {{-- Send --}}
                    <button
                        type="submit"
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-600 text-white transition hover:bg-primary-700 disabled:bg-primary-200 disabled:text-white dark:disabled:bg-primary-900/40 dark:disabled:text-primary-300"
                        :disabled="!message.trim() || submitting"
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
                        @click="message = @js($prompt['prompt']); $nextTick(() => submit())"
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
                            @click="message = @js($insight->prompt); $nextTick(() => submit())"
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
</x-filament-panels::page>
