<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseAuth;

class Login extends BaseAuth
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
        ];
    }
}
