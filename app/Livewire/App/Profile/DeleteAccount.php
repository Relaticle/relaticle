<?php

declare(strict_types=1);

namespace App\Livewire\App\Profile;

use App\Actions\Jetstream\DeleteTeam;
use App\Livewire\BaseLivewireComponent;
use App\Models\Team;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class DeleteAccount extends BaseLivewireComponent
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('profile.sections.delete_account.title'))
                    ->description(__('profile.sections.delete_account.description'))
                    ->aside()
                    ->schema([
                        TextEntry::make('deleteAccountNotice')
                            ->hiddenLabel()
                            ->state(fn () => __('profile.sections.delete_account.notice')),
                        Actions::make([
                            Action::make('deleteAccount')
                                ->label(__('profile.actions.delete_account'))
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading(__('profile.sections.delete_account.title'))
                                ->modalDescription(__('profile.modals.delete_account.notice'))
                                ->modalSubmitActionLabel(__('profile.actions.delete_account'))
                                ->modalCancelAction(false)
                                ->form([
                                    Forms\Components\TextInput::make('password')
                                        ->password()
                                        ->revealable()
                                        ->label(__('profile.form.password.label'))
                                        ->required()
                                        ->currentPassword(),
                                ])
                                ->action(fn (array $data): \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse => $this->deleteAccount()),
                        ]),
                    ]),
            ]);
    }

    /**
     * Delete the current user.
     */
    public function deleteAccount(): Redirector|RedirectResponse
    {
        $user = filament()->auth()->user();

        DB::transaction(function () use ($user): void {
            // Handle teams if teams feature is enabled
            if (config('jetstream.features.teams', false)) {
                $user->teams()->detach();

                $user->ownedTeams->each(function (Team $team): void {
                    app(DeleteTeam::class)->delete($team);
                });
            }

            // Delete profile photo if profile photos feature is enabled
            if (method_exists($user, 'deleteProfilePhoto')) {
                $user->deleteProfilePhoto();
            }

            // Delete API tokens if they exist
            if (method_exists($user, 'tokens')) {
                $user->tokens?->each->delete();
            }

            $user->delete();
        });

        filament()->auth()->logout();

        return redirect(filament()->getLoginUrl());
    }

    public function render(): View
    {
        return view('livewire.app.profile.delete-account');
    }
}
