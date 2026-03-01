<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Illuminate\Support\Facades\Http;
use Relaticle\Workflow\Actions\Concerns\PreventsSSRF;

class SendWebhookAction extends BaseAction
{
    use PreventsSSRF;

    /**
     * Execute the send webhook action, posting a payload to the configured URL.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'url' (string), 'payload' (array)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $url = $config['url'] ?? '';
        $payload = $config['payload'] ?? [];

        $this->validateUrl($url);

        $timeout = (int) config('workflow.action_timeout', 30);
        $response = Http::timeout($timeout)->post($url, $payload);

        return [
            'status_code' => $response->status(),
            'success' => $response->successful(),
            'response_body' => $response->json() ?? $response->body(),
        ];
    }

    /**
     * Get a human-readable label for this action.
     */
    public static function label(): string
    {
        return 'Send Webhook';
    }

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [
            'url' => ['type' => 'string', 'label' => 'Webhook URL', 'required' => true],
            'payload' => ['type' => 'object', 'label' => 'Payload', 'required' => false],
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
            'status_code' => ['type' => 'number', 'label' => 'Status Code'],
            'success' => ['type' => 'boolean', 'label' => 'Was Successful'],
            'response_body' => ['type' => 'string', 'label' => 'Response Body'],
        ];
    }
}
