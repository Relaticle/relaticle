<x-guest-layout :title="config('app.name') . ' - ' . __('Documentation')">
    @pushonce('header')
        @vite(['app-modules/Documentation/resources/js/documentation.js', 'app-modules/Documentation/resources/css/documentation.css'])
    @endpushonce

    <x-documentation::content
        :content="$documentContent"
        :table-of-contents="$tableOfContents"
        :document-types="$documentTypes"
        :current-type="$currentType"/>
</x-guest-layout>
