<x-guest-layout :title="config('app.name') . ' - ' . __('The Next-Generation Open-Source CRM Platform')">
    @include('home.partials.hero')
    @include('home.partials.features')
    @include('home.partials.community')
</x-guest-layout>
