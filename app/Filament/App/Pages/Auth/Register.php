<?php

declare(strict_types=1);

namespace App\Filament\App\Pages\Auth;

use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseAuth;

final class Register extends BaseAuth
{
    #[\Override]
    public function form(Form $form): Form
    {
        return $form
            ->schema([])
            ->statePath('data');
    }

    #[\Override]
    protected function getFormActions(): array
    {
        return [
        ];
    }
}
