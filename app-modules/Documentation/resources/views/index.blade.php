<x-guest-layout :title="config('app.name') . ' - ' . __('The Next-Generation Open-Source CRM Platform')">
    @pushonce('header')
        @vite(['app-modules/Documentation/resources/js/documentation.js', 'app-modules/Documentation/resources/css/documentation.css'])
    @endpushonce

    <x-documentation::content
        :content="$documentContent"
        :document-types="$documentTypes"
        :current-type="$currentType"/>
</x-guest-layout>
