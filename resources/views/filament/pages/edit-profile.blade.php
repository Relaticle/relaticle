<x-filament-panels::page>
    @if (Laravel\Fortify\Features::canUpdateProfileInformation())
        @livewire(Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm::class)

        <x-section-border/>
    @endif

    @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
        <div class="mt-10 sm:mt-0">
            @livewire(Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
        </div>

        <x-section-border/>
    @endif

    @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
        <div class="mt-10 sm:mt-0">
            @livewire(Laravel\Jetstream\Http\Livewire\TwoFactorAuthenticationForm::class)
        </div>

        <x-section-border/>
    @endif

    <div class="mt-10 sm:mt-0">
        @livewire(Laravel\Jetstream\Http\Livewire\LogoutOtherBrowserSessionsForm::class)
    </div>

    @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
        <x-section-border/>

        <div class="mt-10 sm:mt-0">
            @livewire(Laravel\Jetstream\Http\Livewire\DeleteUserForm::class)
        </div>
    @endif
</x-filament-panels::page>
