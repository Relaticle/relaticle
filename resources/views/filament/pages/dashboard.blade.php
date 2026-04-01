<x-filament-panels::page>
    <div class="space-y-6">
        {{-- CRM Summary Widgets --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach($summary['record_counts'] ?? [] as $entity => $count)
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ ucfirst($entity) }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($count) }}</p>
                </div>
            @endforeach
        </div>

        {{-- Full Chat Interface --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <livewire:chat.chat-interface :conversation-id="$conversationId" />
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
