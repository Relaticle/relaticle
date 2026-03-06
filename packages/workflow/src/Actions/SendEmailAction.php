<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
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

    public static function hasSideEffects(): bool
    {
        return true;
    }

    public static function category(): string
    {
        return 'Communication';
    }

    public static function icon(): string
    {
        return 'heroicon-o-envelope';
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

    public static function filamentForm(): array
    {
        return [
            TextInput::make('to')
                ->label('Recipient Email')
                ->email()
                ->required()
                ->placeholder('recipient@example.com')
                ->live(onBlur: true),
            TextInput::make('subject')
                ->label('Subject')
                ->required()
                ->placeholder('Notification subject')
                ->live(onBlur: true),
            Textarea::make('body')
                ->label('Email Body')
                ->required()
                ->rows(5)
                ->placeholder('Email content...')
                ->live(onBlur: true),
            ViewField::make('email_preview')
                ->label('Preview')
                ->view('workflow::forms.email-preview'),
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
