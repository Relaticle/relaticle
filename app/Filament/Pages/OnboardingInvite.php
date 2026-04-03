<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\Jetstream\InviteTeamMember;
use App\Filament\Resources\CompanyResource;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

final class OnboardingInvite extends Page
{
    protected string $view = 'filament.pages.onboarding-invite';

    protected static ?string $slug = 'onboarding/invite';

    protected static bool $shouldRegisterNavigation = false;

    /** @var list<string> */
    public array $emails = ['', ''];

    public function mount(): void
    {
        $team = Filament::getTenant();

        if (! $team instanceof Team) {
            return;
        }

        if ($team->allUsers()->count() > 1 || ! $team->isPersonalTeam()) {
            $this->redirect(CompanyResource::getUrl('index'));
        }
    }

    public function getTitle(): string
    {
        return 'Collaborate with your team';
    }

    public function getSubheading(): string
    {
        return 'The more your teammates use Relaticle, the more powerful it becomes.';
    }

    public function addEmailField(): void
    {
        $this->emails[] = '';
    }

    public function sendInvites(): void
    {
        /** @var User $user */
        $user = auth('web')->user();

        /** @var Team $team */
        $team = Filament::getTenant();

        $validEmails = array_filter($this->emails, fn (string $email): bool => filled($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false);

        if ($validEmails === []) {
            Notification::make()
                ->title('Please enter at least one valid email address.')
                ->danger()
                ->send();

            return;
        }

        $sentCount = 0;

        foreach ($validEmails as $email) {
            try {
                resolve(InviteTeamMember::class)->invite($user, $team, $email, 'editor');
                $sentCount++;
            } catch (ValidationException) {
                continue;
            }
        }

        if ($sentCount > 0) {
            Notification::make()
                ->title("Sent {$sentCount} invitation(s) successfully.")
                ->success()
                ->send();
        }

        $this->redirect(CompanyResource::getUrl('index'));
    }

    public function skip(): void
    {
        $this->redirect(CompanyResource::getUrl('index'));
    }
}
