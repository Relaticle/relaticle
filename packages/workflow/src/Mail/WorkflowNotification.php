<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WorkflowNotification extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * The email body content.
     */
    public string $body;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subject, string $body)
    {
        $this->subject = $subject;
        $this->body = $body;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->html($this->body);
    }
}
