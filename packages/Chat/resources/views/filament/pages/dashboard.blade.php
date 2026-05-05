<x-filament-panels::page>
    <div
        x-data="{
            message: '',
            submitting: false,

            submit() {
                const text = this.message.trim();
                if (!text || this.submitting) return;
                this.submitting = true;

                const url = new URL(@js(\App\Filament\Pages\ChatConversation::getUrl()), window.location.origin);
                url.searchParams.set('message', text);

                window.location.href = url.toString();
            }
        }"
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
                <textarea
                    x-model="message"
                    @keydown.enter.prevent="if(!$event.shiftKey) submit()"
                    placeholder="Ask anything..."
                    rows="3"
                    class="block w-full resize-none rounded-2xl border-0 bg-transparent px-4 py-3 pr-12 text-sm leading-6 text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                    :disabled="submitting"
                ></textarea>
                <button
                    type="submit"
                    class="absolute bottom-2.5 right-2.5 flex h-7 w-7 items-center justify-center rounded-lg bg-primary-600 text-white transition hover:bg-primary-700 disabled:opacity-40"
                    :disabled="!message.trim() || submitting"
                >
                    <x-heroicon-s-arrow-up class="h-4 w-4" />
                </button>
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
