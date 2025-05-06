<x-guest-layout :title="'Terms of Service - ' . config('app.name')">
    <x-legal-document
        title="Terms of Service"
        subtitle="Use of the Relaticle website and all related services is subject to the following terms of service."
        :content="$terms"
    />
</x-guest-layout>
