<x-guest-layout 
    :title="config('app.name') . ' - ' . __('The Next-Generation Open-Source CRM Platform')"
    description="Relaticle is an open-source CRM platform designed for modern businesses. Manage your customers, leads, and opportunities with ease."
    :ogTitle="config('app.name') . ' - Open-Source CRM Platform'"
    ogDescription="Discover Relaticle, the next-generation open-source CRM platform. Powerful, flexible, and built for modern businesses."
    :ogImage="url('/images/og-image.jpg')">
    @include('home.partials.hero')
    @include('home.partials.features')
    @include('home.partials.community')
</x-guest-layout>
