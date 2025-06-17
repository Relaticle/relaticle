<?php

declare(strict_types=1);

namespace App\Filament\App\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Auth\Pages\Register as BaseRegister;

final class Register extends BaseRegister
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/register.form.email.label'))
            ->email()
            ->rules(['email:rfc,dns'])
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }
}
