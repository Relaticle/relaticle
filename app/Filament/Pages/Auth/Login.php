<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Concerns\DetectsTeamInvitation;
use Filament\Actions\Action;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

final class Login extends \Filament\Auth\Pages\Login
{
    use DetectsTeamInvitation;

    public function getSubheading(): string|Htmlable|null
    {
        $parentSubheading = parent::getSubheading();
        $invitationSubheading = $this->getTeamInvitationSubheading();

        if ($invitationSubheading !== null && $parentSubheading !== null) {
            $parentHtml = $parentSubheading instanceof Htmlable
                ? $parentSubheading->toHtml()
                : e($parentSubheading);

            return new HtmlString($invitationSubheading->toHtml().'<br>'.$parentHtml);
        }

        return $invitationSubheading ?? $parentSubheading;
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->size(Size::Medium)
            ->label(__('filament-panels::auth/pages/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }
}
