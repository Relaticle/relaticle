<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Concerns\DetectsTeamInvitation;
use App\Models\TeamInvitation;
use Filament\Actions\Action;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        $user = $this->getUserModel()::query()->create($data);

        $intendedUrl = session('url.intended', '');

        if (str_contains((string) $intendedUrl, '/team-invitations/')) {
            $path = parse_url((string) $intendedUrl, PHP_URL_PATH);
            $segments = $path ? explode('/', trim($path, '/')) : [];
            $invitationIndex = array_search('team-invitations', $segments, true);

            if ($invitationIndex !== false && isset($segments[$invitationIndex + 1])) {
                $invitation = TeamInvitation::query()->whereKey($segments[$invitationIndex + 1])->first();

                if ($invitation && $invitation->email === $data['email']) {
                    $user->forceFill(['email_verified_at' => now()])->save();
                }
            }
        }

        return $user;
    }
}
