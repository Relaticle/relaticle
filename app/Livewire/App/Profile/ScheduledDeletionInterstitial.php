<?php

declare(strict_types=1);

namespace App\Livewire\App\Profile;

use App\Actions\Jetstream\CancelUserDeletion;
use App\Livewire\BaseLivewireComponent;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;
use Livewire\Attributes\Layout;

#[Layout('layouts.filament-standalone')]
final class ScheduledDeletionInterstitial extends BaseLivewireComponent
{
    public function mount(): Redirector|RedirectResponse|null
    {
        if (! $this->authUser()->isScheduledForDeletion()) {
            return redirect(Filament::getHomeUrl());
        }

        return null;
    }

    public function cancelDeletion(): Redirector|RedirectResponse
    {
        resolve(CancelUserDeletion::class)->cancel($this->authUser());

        $this->sendNotification('Account deletion cancelled');

        return redirect(Filament::getHomeUrl());
    }

    public function logout(): Redirector|RedirectResponse
    {
        filament()->auth()->logout();

        return redirect(Filament::getLoginUrl());
    }

    public function cancelDeletionAction(): Action
    {
        return Action::make('cancelDeletion')
            ->label('Cancel Deletion')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Cancel account deletion?')
            ->modalDescription('Your account and data will be restored.')
            ->action(fn (): Redirector|RedirectResponse => $this->cancelDeletion());
    }

    public function logoutAction(): Action
    {
        return Action::make('logout')
            ->label('Continue to Deletion')
            ->color('gray')
            ->link()
            ->action(fn (): Redirector|RedirectResponse => $this->logout());
    }

    public function render(): View
    {
        return view('livewire.app.profile.scheduled-deletion-interstitial');
    }
}
