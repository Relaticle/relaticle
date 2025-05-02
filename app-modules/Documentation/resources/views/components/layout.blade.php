<x-guest-layout :title="config('app.name') . ' - ' . __('Documentation')">
    @pushonce('header')
        @vite(['app-modules/Documentation/resources/js/documentation.js', 'app-modules/Documentation/resources/css/documentation.css'])
    @endpushonce

    <div class="py-8 md:py-12 dark:bg-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Main Documentation Container -->
            <div class="documentation-content">
                {{ $slot }}
            </div>
        </div>
    </div>

</x-guest-layout>
