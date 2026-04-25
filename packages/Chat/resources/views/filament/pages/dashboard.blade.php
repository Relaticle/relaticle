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
        class="mx-auto max-w-2xl py-12"
    >
        {{-- Greeting --}}
        <h1 class="text-3xl font-semibold text-gray-950 dark:text-white">
            {{ $this->getGreeting() }}
        </h1>

        {{-- Recent chat link --}}
        @if($recentChatId)
            <a
                href="{{ \App\Filament\Pages\ChatConversation::getUrl(['conversationId' => $recentChatId]) }}"
                class="mt-3 inline-flex items-center gap-1.5 text-sm text-gray-500 transition hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
            >
                <x-heroicon-o-arrow-path class="h-3.5 w-3.5" />
                <span>Recent chat &middot; {{ \Illuminate\Support\Str::limit($recentChatTitle ?? 'Untitled', 50) }}</span>
            </a>
        @endif

        {{-- Proactive insights --}}
        @php
            $insights = $this->getInsights();
        @endphp
        @if($insights->isNotEmpty())
            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($insights as $insight)
                    @php
                        $severityClasses = match ($insight->severity) {
                            'warning' => 'border-amber-200 bg-amber-50 hover:border-amber-300 dark:border-amber-900/40 dark:bg-amber-900/10',
                            'success' => 'border-emerald-200 bg-emerald-50 hover:border-emerald-300 dark:border-emerald-900/40 dark:bg-emerald-900/10',
                            default => 'border-blue-200 bg-blue-50 hover:border-blue-300 dark:border-blue-900/40 dark:bg-blue-900/10',
                        };
                        $countClasses = match ($insight->severity) {
                            'warning' => 'text-amber-600 dark:text-amber-400',
                            'success' => 'text-emerald-600 dark:text-emerald-400',
                            default => 'text-blue-600 dark:text-blue-400',
                        };
                    @endphp
                    <button
                        type="button"
                        @click="message = @js($insight->prompt); $nextTick(() => submit())"
                        class="rounded-xl border p-4 text-left shadow-sm transition {{ $severityClasses }}"
                    >
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-bold {{ $countClasses }}">{{ $insight->count }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $insight->title }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $insight->description }}</p>
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Chat input --}}
        <div class="mt-6">
            <form @submit.prevent="submit()">
                <div class="relative">
                    <textarea
                        x-model="message"
                        @keydown.enter.prevent="if(!$event.shiftKey) submit()"
                        placeholder="Ask anything..."
                        rows="3"
                        class="w-full resize-none rounded-xl border border-gray-300 bg-white px-4 py-3 pr-12 text-sm shadow-sm transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                        :disabled="submitting"
                    ></textarea>
                    <div class="absolute bottom-3 right-3 flex items-center gap-2">
                        <button
                            type="submit"
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-600 text-white transition hover:bg-primary-700 disabled:opacity-40"
                            :disabled="!message.trim() || submitting"
                        >
                            <x-heroicon-s-arrow-up class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Suggested prompts --}}
        @php($suggestedPrompts = $this->getSuggestedPrompts())
        @if(!empty($suggestedPrompts))
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($suggestedPrompts as $prompt)
                    <button
                        @click="message = @js($prompt['prompt']); $nextTick(() => submit())"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-primary-600 dark:hover:bg-primary-900/20 dark:hover:text-primary-400"
                    >
                        {{ $prompt['label'] }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
