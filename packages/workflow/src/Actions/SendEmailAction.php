<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Illuminate\Support\Facades\Mail;
use Relaticle\Workflow\Mail\WorkflowNotification;

class SendEmailAction extends BaseAction
{
    /**
     * Execute the send email action, sending an email to the configured recipient.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'to' (string), 'subject' (string), 'body' (string)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $to = $config['to'] ?? '';
        $subject = $config['subject'] ?? 'Workflow Notification';
        $body = $config['body'] ?? '';

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: {$to}");
        }

        try {
            Mail::to($to)->send(new WorkflowNotification($subject, $body));

            return [
                'sent' => true,
                'to' => $to,
            ];
        } catch (\Throwable $e) {
            return [
                'sent' => false,
                'to' => $to,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get a human-readable label for this action.
     */
    public static function label(): string
    {
        return 'Send Email';
    }

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [
            'to' => ['type' => 'string', 'label' => 'Recipient Email', 'required' => true],
            'subject' => ['type' => 'string', 'label' => 'Subject', 'required' => true],
            'body' => ['type' => 'string', 'label' => 'Email Body', 'required' => true],
        ];
    }

    /**
     * Get the output schema describing what variables this action produces.
     *
     * @return array<string, array{type: string, label: string}>
     */
    public static function outputSchema(): array
    {
        return [
            'sent' => ['type' => 'boolean', 'label' => 'Was Sent'],
            'to' => ['type' => 'string', 'label' => 'Recipient'],
        ];
    }
}
