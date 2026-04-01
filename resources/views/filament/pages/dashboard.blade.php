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

        {{-- Chat input --}}
        <div class="mt-6">
            <form @submit.prevent="submit()">
                <div class="relative">
                    <textarea
                        x-model="message"
                        @keydown.enter.prevent="submit()"
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
                        @click="message = '{{ addslashes($prompt['prompt']) }}'; $nextTick(() => submit())"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-primary-600 dark:hover:bg-primary-900/20 dark:hover:text-primary-400"
                    >
                        {{ $prompt['label'] }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
