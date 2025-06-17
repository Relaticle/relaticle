<?php

declare(strict_types=1);

namespace App\Filament\App\Pages\Auth;

use Filament\Schemas\Components\Component;
use Filament\Forms\Components\TextInput;

final class Register extends \Filament\Auth\Pages\Register
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/register.form.email.label'))
            ->email()
            ->rules(['email:rfc,dns'])
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }
}
