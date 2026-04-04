<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TeamDeletionScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Team $team,
        private readonly User $owner,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->team->name} is scheduled for deletion")
            ->line("{$this->team->name} has been scheduled for deletion on {$this->team->scheduled_deletion_at->format('F j, Y')} by {$this->owner->name}.")
            ->line('All team data, including contacts, companies, tasks, opportunities, and notes, will be permanently removed after this date.')
            ->salutation('Thank you for using Relaticle.');
    }
}
