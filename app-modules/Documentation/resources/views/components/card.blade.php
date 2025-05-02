@props([
    'title',
    'description',
    'link',
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'group bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 hover:border-gray-200 dark:hover:border-gray-700 transition-all duration-300 hover:shadow-sm transform hover:-translate-y-1']) }}>
    <a href="{{ $link }}" class="block p-6">
        <div class="space-y-4">
            <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-800 text-primary dark:text-primary-400 group-hover:bg-primary/10 dark:group-hover:bg-primary/20 transition-colors duration-300">
                @if($icon)
                    <x-dynamic-component :component="$icon" class="h-5 w-5" />
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                @endif
            </div>
            
            <div>
                <h3 class="text-lg font-medium text-black dark:text-white group-hover:text-primary dark:group-hover:text-primary-400 transition-colors duration-300">
                    {{ $title }}
                </h3>
                <div class="mt-2 text-gray-600 dark:text-gray-300 text-sm leading-relaxed">
                    @if($description)
                        <p>{{ $description }}</p>
                    @else
                        {{ $slot }}
                    @endif
                </div>
            </div>
            
            <div class="pt-2">
                <span class="inline-flex items-center text-sm font-medium text-primary dark:text-primary-400 group-hover:text-primary-600 dark:group-hover:text-primary-300 transition-colors duration-300">
                    Read documentation
                    <svg class="ml-1 h-4 w-4 transform transition-transform duration-300 group-hover:translate-x-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            </div>
        </div>
    </a>
</div>
