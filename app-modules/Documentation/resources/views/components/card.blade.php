@props([
    'title',
    'description',
    'link',
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'group bg-white  dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 transition-all duration-300 hover:shadow-sm transform hover:-translate-y-1']) }}>
    <a href="{{ $link }}" class="block p-6">
        <div class="space-y-4">
            <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-800 text-primary dark:text-primary-400 group-hover:bg-primary/10 dark:group-hover:bg-primary/20 transition-colors duration-300">
                @if($icon)
                    <x-dynamic-component :component="$icon" class="h-5 w-5" />
                @else
                    <x-heroicon-o-document-text class="h-5 w-5" />
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
                    <x-heroicon-s-chevron-right class="ml-1 h-4 w-4 transform transition-transform duration-300 group-hover:translate-x-0.5" />
                </span>
            </div>
        </div>
    </a>
</div>
