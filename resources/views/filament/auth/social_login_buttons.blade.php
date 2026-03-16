<div class="space-y-3">
    @foreach ($enabledProviders as $provider)
        <x-filament::button
            :href="route('auth.socialite.redirect', $provider->value)"
            :spa-mode="false"
            tag="a"
            color="gray"
            class="w-full justify-center"
        >
            <span class="flex items-center">
                <x-dynamic-component
                    :component="'icons.' . $provider->value"
                    class="w-5 h-5 mr-2"
                />
                Continue with {{ $provider->label() }}
            </span>
        </x-filament::button>
    @endforeach

    @if (count($enabledProviders) > 0)
        <div class="flex items-center justify-center my-4">
            <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
            <span class="mx-2 text-gray-500 dark:text-gray-400">or</span>
            <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
        </div>
    @endif
</div>
