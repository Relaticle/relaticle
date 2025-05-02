<x-documentation::layout :title="config('app.name') . ' - ' . __('Documentation')" class="documentation-hub">
    <div class="max-w-5xl mx-auto">
        <!-- Tech Badge - Simplified -->
        <div class="flex justify-center mb-12">
            <div class="inline-flex items-center px-3 py-1.5 border border-gray-100 dark:border-gray-800 rounded-full text-xs shadow-sm">
                <span class="text-gray-500 dark:text-gray-400 mr-2">Documentation</span>
                <div class="flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span class="font-medium text-gray-700 dark:text-gray-300">Resources</span>
                </div>
            </div>
        </div>

        <!-- Hero Text - Enhanced Typography -->
        <div class="text-center space-y-6 max-w-3xl mx-auto mb-12">
            <h1 class="text-4xl sm:text-5xl font-bold text-black dark:text-white leading-[1.1] tracking-tight">
                <span class="relative inline-block">
                    <span class="relative z-10">Documentation</span>
                    <span class="absolute bottom-2 sm:left-0 right-1/4 w-1/2 sm:w-full h-3 bg-primary/10 dark:bg-primary/20 -rotate-1 z-0"></span>
                </span>
            </h1>

            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto leading-relaxed">
                Welcome to the Relaticle documentation hub. Here you'll find guides and resources to help you get the
                most out of Relaticle CRM.
            </p>
        </div>

        <div class="mb-8">
            <x-documentation::search-form />
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @php
                $documentIcons = [
                    'business' => 'heroicon-o-briefcase',
                    'technical' => 'heroicon-o-code-bracket',
                    'quickstart' => 'heroicon-o-rocket-launch',
                    'api' => 'heroicon-o-variable'
                ];
            @endphp
            
            @foreach($documentTypes as $type => $document)
                <x-documentation::card
                    :title="$document['title']"
                    :description="$document['description'] ?? ''"
                    :link="route('documentation.show', ['type' => $type])"
                    :icon="$documentIcons[$type] ?? null"
                />
            @endforeach
        </div>
    </div>
</x-documentation::layout>
