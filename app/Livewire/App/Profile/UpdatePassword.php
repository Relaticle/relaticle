<?php

declare(strict_types=1);

namespace App\Livewire\App\Profile;

use App\Actions\Fortify\UpdateUserPassword as UpdateUserPasswordAction;
use App\Livewire\BaseLivewireComponent;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

final class UpdatePassword extends BaseLivewireComponent
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        // Initialize empty form
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('profile.sections.update_password.title'))
                    ->aside()
                    ->description(__('profile.sections.update_password.description'))
                    ->schema([
                        TextInput::make('currentPassword')
                            ->label(__('profile.form.current_password.label'))
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->required()
                            ->autocomplete('current-password')
                            ->currentPassword(),
                        TextInput::make('password')
                            ->label(__('profile.form.new_password.label'))
                            ->password()
                            ->required()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->rule(Password::default())
                            ->autocomplete('new-password')
                            ->dehydrated(fn (mixed $state): bool => filled($state))
                            ->live(debounce: 500)
                            ->same('passwordConfirmation'),
                        TextInput::make('passwordConfirmation')
                            ->label(__('profile.form.confirm_password.label'))
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->required()
                            ->visible(
                                fn (Get $get): bool => filled($get('password'))
                            ),
                        Actions::make([
                            Action::make('save')
                                ->label(__('profile.actions.save'))
                                ->submit('updatePassword'),
                        ]),
                    ]),
            ])
            ->statePath('data')
            ->model($this->authUser());
    }

    public function updatePassword(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->sendRateLimitedNotification($exception);

            return;
        }

        $data = $this->form->getState();

        // Map form fields to Fortify action fields
        $input = [
            'current_password' => $data['currentPassword'],
            'password' => $data['password'],
            'password_confirmation' => $data['passwordConfirmation'],
        ];

        app(UpdateUserPasswordAction::class)->update($this->authUser(), $input);

        if (request()->hasSession() && isset($input['password'])) {
            request()->session()->put(['password_hash_'.Filament::getAuthGuard() => Hash::make($input['password'])]);
        }

        $this->data['password'] = null;
        $this->data['currentPassword'] = null;
        $this->data['passwordConfirmation'] = null;

        $this->sendNotification();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.app.profile.update-password');
    }
}
