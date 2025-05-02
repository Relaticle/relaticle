@props(['title' => null, 'header' => null, 'icon' => null, 'url' => null, 'class' => '', 'iconClass' => ''])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-200 hover:shadow-md hover:border-documentation-200 dark:hover:border-documentation-700 ' . $class]) }}>
    @if($header)
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $header }}</h3>
        </div>
    @endif
    
    <div class="p-6">
        @if($title && $icon)
            <div class="flex items-center space-x-3 mb-4">
                @if($icon)
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-documentation-100 dark:bg-documentation-900/30 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-documentation-600 dark:text-documentation-400 {{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            {!! $icon !!}
                        </svg>
                    </div>
                @endif
                @if($title)
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $title }}</h3>
                @endif
            </div>
        @endif

        <div class="prose prose-sm dark:prose-invert prose-p:text-gray-600 dark:prose-p:text-gray-300">
            {{ $slot }}
        </div>

        @if($url)
            <div class="mt-4">
                <a href="{{ $url }}" class="inline-flex items-center text-sm font-medium text-documentation-600 dark:text-documentation-400 hover:text-documentation-700 dark:hover:text-documentation-300 transition-colors">
                    Read more
                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        @endif
    </div>
</div>
