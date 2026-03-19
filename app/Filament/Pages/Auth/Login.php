<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Concerns\DetectsTeamInvitation;
use Filament\Actions\Action;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Support\Htmlable;

final class Login extends \Filament\Auth\Pages\Login
{
    use DetectsTeamInvitation;

    public function getSubheading(): string|Htmlable|null
    {
        $parentSubheading = parent::getSubheading();

        if ($parentSubheading !== null) {
            $subheadingText = $parentSubheading instanceof Htmlable
                ? $parentSubheading->toHtml()
                : $parentSubheading;

            if (! str_contains($subheadingText, 'sign up')) {
                return $parentSubheading;
            }
        }

        return $this->getTeamInvitationSubheading() ?? $parentSubheading;
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->size(Size::Medium)
            ->label(__('filament-panels::auth/pages/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }
}
