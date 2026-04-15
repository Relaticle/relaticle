<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TeamDeletionReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Team $team,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->team->name} will be deleted in 5 days")
            ->line("{$this->team->name} is scheduled for permanent deletion on {$this->team->scheduled_deletion_at->format('F j, Y')}.")
            ->line('This is your final reminder. All data will be permanently removed after this date.')
            ->line('To cancel, log in to your account before the deletion date.')
            ->salutation('Thank you for using Relaticle.');
    }
}
