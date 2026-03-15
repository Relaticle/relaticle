<x-documentation::layout :document="null" class="documentation-hub">
    <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(0,0,0,0.015)_1px,transparent_1px),linear-gradient(to_bottom,rgba(0,0,0,0.015)_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,rgba(255,255,255,0.025)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.025)_1px,transparent_1px)] bg-[size:3rem_3rem] [mask-image:radial-gradient(ellipse_70%_50%_at_50%_50%,black_30%,transparent_100%)] pointer-events-none"></div>

    <div class="max-w-4xl mx-auto relative">
        <div class="text-center space-y-5 max-w-3xl mx-auto mb-12">
            <h1 class="font-display text-4xl sm:text-5xl font-bold text-gray-950 dark:text-white leading-[1.1] tracking-[-0.02em]">
                Documentation
            </h1>
            <p class="text-lg text-gray-500 dark:text-gray-400 max-w-2xl mx-auto leading-relaxed">
                Guides and resources to help you get the most out of Relaticle CRM.
            </p>
        </div>

        <div class="mb-12 max-w-lg mx-auto">
            <x-documentation::search-form />
        </div>

        {{-- Getting Started -- full width entry point --}}
        <a href="{{ route('documentation.show', ['type' => 'getting-started']) }}"
           class="group block p-6 md:p-8 bg-gray-50/60 dark:bg-white/[0.02] border-t border-b border-gray-200/60 dark:border-white/[0.04] transition-colors duration-200 hover:bg-gray-100/60 dark:hover:bg-white/[0.04]">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-primary/8 dark:bg-primary/15 text-primary dark:text-primary-400 group-hover:bg-primary/12 transition-colors duration-200">
                    <x-ri-rocket-2-line class="w-4.5 h-4.5"/>
                </div>
                <div class="flex-1">
                    <h3 class="font-display text-[15px] font-semibold text-gray-900 dark:text-white">Getting Started</h3>
                    <p class="text-[13px] leading-relaxed text-gray-500 dark:text-gray-400 mt-1">Set up your account, invite your team, and learn the basics in 5 minutes.</p>
                </div>
                <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-900 dark:text-white group-hover:text-primary dark:group-hover:text-primary-400 transition-colors duration-200 shrink-0">
                    Read docs
                    <x-ri-arrow-right-line class="w-3 h-3 group-hover:translate-x-0.5 transition-transform duration-200"/>
                </span>
            </div>
        </a>

        {{-- Remaining docs -- divider grid --}}
        @php
            $documentIcons = [
                'import' => 'ri-upload-2-line',
                'developer' => 'ri-code-s-slash-line',
                'api' => 'ri-terminal-box-line',
                'mcp' => 'ri-cpu-line',
            ];
            $remaining = collect($documentTypes)->except('getting-started');
        @endphp

        <div class="border-t border-gray-200/60 dark:border-white/[0.04] divide-y divide-gray-200/60 dark:divide-white/[0.04]">
            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200/60 dark:divide-white/[0.04]">
                @foreach($remaining as $type => $document)
                    @if($loop->index % 2 === 0 && !$loop->first)
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200/60 dark:divide-white/[0.04]">
                    @endif
                    <x-documentation::card
                        :title="$document['title']"
                        :description="$document['description'] ?? ''"
                        :link="isset($document['url']) ? $document['url'] : route('documentation.show', ['type' => $type])"
                        :icon="$documentIcons[$type] ?? null"
                    />
                @endforeach
            </div>
        </div>
    </div>
</x-documentation::layout>
