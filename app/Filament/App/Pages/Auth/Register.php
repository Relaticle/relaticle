<?php

declare(strict_types=1);

namespace App\Filament\App\Pages\Auth;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

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

    private function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        Placeholder::make('terms')
                            ->label(new HtmlString('By signing up you agree to the <a href="'.URL::getPublicUrl('terms-of-service').'" target="_blank" class="text-primary-600 hover:text-primary-500">Terms of Service</a> & <a href="'.URL::getPublicUrl('privacy-policy').'" target="_blank" class="text-primary-600 hover:text-primary-500">Privacy Policy</a>.')),
                    ])
                    ->statePath('data'),
            ),
        ];
    }
}
