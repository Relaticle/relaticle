<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

final class DeletionReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $subjectName,
        private readonly Carbon $deletionDate,
        private readonly string $type,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->type === 'user'
            ? 'Final reminder: your account will be deleted in 5 days'
            : "Final reminder: {$this->subjectName} will be deleted in 5 days";

        $firstLine = $this->type === 'user'
            ? "Your account is scheduled for permanent deletion on {$this->deletionDate->format('F j, Y')}."
            : "{$this->subjectName} is scheduled for permanent deletion on {$this->deletionDate->format('F j, Y')}.";

        return (new MailMessage)
            ->subject($subject)
            ->line($firstLine)
            ->line('This is your final reminder. All data will be permanently removed after this date.')
            ->line('To cancel, log in to your account before the deletion date.')
            ->salutation('Thank you for using Relaticle.');
    }
}
