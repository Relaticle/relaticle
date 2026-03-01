<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Notifications;

use Illuminate\Notifications\Notification;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Models\WorkflowRun;

class WorkflowRunFailedNotification extends Notification
{
    public function __construct(
        public Workflow $workflow,
        public WorkflowRun $run,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "Workflow '{$this->workflow->name}' failed",
            'body' => $this->run->error_message ?? 'An error occurred during execution.',
            'workflow_id' => $this->workflow->id,
            'run_id' => $this->run->id,
        ];
    }
}
