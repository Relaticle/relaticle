<x-filament-panels::page>
    <div class="space-y-8">
        {{-- Hero Chat Input --}}
        <div class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="mb-2 text-center text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                Ask anything about your CRM
            </h2>
            <p class="mb-6 text-center text-sm text-gray-500 dark:text-gray-400">
                Search records, create entries, get summaries, and more.
            </p>

            <form
                x-data="{ message: '' }"
                x-on:submit.prevent="
                    if (message.trim()) {
                        window.location.href = '{{ \App\Filament\Pages\Chat::getUrl() }}' + '?message=' + encodeURIComponent(message);
                    }
                "
                class="mx-auto max-w-2xl"
            >
                <div class="flex gap-2">
                    <input
                        x-model="message"
                        type="text"
                        placeholder="Ask anything..."
                        class="fi-input block w-full rounded-lg border-gray-300 bg-white/50 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                    <button
                        type="submit"
                        class="fi-btn fi-btn-size-md fi-btn-color-primary rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                    >
                        Send
                    </button>
                </div>
            </form>

            {{-- Suggested Prompts --}}
            @if(count($suggestedPrompts) > 0)
                <div class="mx-auto mt-4 flex max-w-2xl flex-wrap justify-center gap-2">
                    @foreach($suggestedPrompts as $prompt)
                        <a
                            href="{{ \App\Filament\Pages\Chat::getUrl() }}?message={{ urlencode($prompt['prompt']) }}"
                            class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-primary-50 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-primary-900/20 dark:hover:text-primary-400"
                        >
                            {{ $prompt['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- CRM Summary Widgets --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach($summary['record_counts'] ?? [] as $entity => $count)
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ ucfirst($entity) }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($count) }}</p>
                </div>
            @endforeach
        </div>

        {{-- Recent Activity --}}
        @if(!empty($summary['recent_activity']))
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-4 text-lg font-semibold text-gray-950 dark:text-white">This Week</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @foreach($summary['recent_activity'] as $metric => $count)
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/20">
                                <span class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ $count }}</span>
                            </div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ str_replace('_', ' ', ucfirst(str_replace('_this_week', '', $metric))) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
