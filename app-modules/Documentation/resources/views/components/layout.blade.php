@php
    $documentTitle = '';
    if (isset($document)) {
        if (is_array($document)) {
            $documentTitle = $document['title'] ?? '';
        } else {
            $documentTitle = $document->title ?? '';
        }
    }
@endphp

<x-guest-layout 
    :title="!empty($documentTitle) ? $documentTitle . ' - ' . config('app.name') . ' ' . __('Documentation') : config('app.name') . ' - ' . __('Documentation')"
    :description="!empty($documentTitle) ? $documentTitle . ' - Relaticle Documentation' : 'Comprehensive documentation for Relaticle - The Next-Generation Open-Source CRM Platform. Learn how to install, configure, and use Relaticle effectively.'"
    :ogTitle="!empty($documentTitle) ? $documentTitle . ' - ' . config('app.name') . ' ' . __('Documentation') : config('app.name') . ' - Documentation'"
    :ogDescription="!empty($documentTitle) ? 'Learn about ' . $documentTitle . ' in the Relaticle documentation.' : 'Explore the Relaticle documentation. Learn how to get started, configuration options, and advanced features of our open-source CRM platform.'">
    @pushonce('header')
        @vite(['app-modules/Documentation/resources/js/documentation.js', 'app-modules/Documentation/resources/css/documentation.css'])
    @endpushonce

    <div class="py-16 md:py-24 bg-white dark:bg-black relative">
        <!-- Subtle background elements -->
        <div class="absolute inset-0 bg-grid-pattern opacity-[0.01] dark:opacity-[0.02]"></div>
        <div class="absolute top-24 left-24 w-36 h-36 md:w-96 md:h-96 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 right-24 w-36 h-36 md:w-96 md:h-96 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <!-- Main Documentation Container -->
            <div class="documentation-content">
                {{ $slot }}
            </div>
        </div>
    </div>

</x-guest-layout>
