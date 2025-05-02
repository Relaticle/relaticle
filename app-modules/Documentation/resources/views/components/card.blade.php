@props(['title' => null, 'header' => null, 'icon' => null, 'url' => null, 'class' => '', 'iconClass' => ''])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-300 ' . $class]) }}>
    @if($header)
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $header }}</h3>
        </div>
    @endif
    
    <div class="p-6">
        @if($title || $icon)
            <div class="flex items-center space-x-4 mb-5">
                @if($icon)
                    <div class="flex-shrink-0 h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center shadow-sm card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary-600 dark:text-primary-400 {{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            {!! $icon !!}
                        </svg>
                    </div>
                @endif
                @if($title)
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                @endif
            </div>
        @endif

        <div class="prose prose-sm dark:prose-invert prose-p:text-gray-600 dark:prose-p:text-gray-300">
            {{ $slot }}
        </div>

        @if($url)
            <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ $url }}" class="group inline-flex items-center text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
                    Read more
                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4 transform transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        @endif
    </div>
</div>
