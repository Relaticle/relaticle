<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Concerns\DetectsTeamInvitation;
use Filament\Actions\Action;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Support\Htmlable;

final class Register extends BaseRegister
{
    use DetectsTeamInvitation;

    public function getSubheading(): string|Htmlable|null
    {
        return $this->getTeamInvitationSubheading() ?? parent::getSubheading();
    }

    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/register.form.email.label'))
            ->email()
            ->rules(['email:rfc,dns'])
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }

    public function getRegisterFormAction(): Action
    {
        return Action::make('register')
            ->size(Size::Medium)
            ->label(__('filament-panels::auth/pages/register.form.actions.register.label'))
            ->submit('register');
    }
}
