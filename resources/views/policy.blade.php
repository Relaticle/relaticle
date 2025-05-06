<x-guest-layout :title="'Privacy Policy - ' . config('app.name')">
    <x-legal-document
        title="Privacy Policy"
        subtitle="How we collect, use, and protect your personal information."
        :content="$policy"
    />
</x-guest-layout>
