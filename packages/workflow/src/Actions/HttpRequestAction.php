<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Illuminate\Support\Facades\Http;
use Relaticle\Workflow\Actions\Concerns\PreventsSSRF;

class HttpRequestAction extends BaseAction
{
    use PreventsSSRF;

    /**
     * Execute the HTTP request action, making a request with the configured method and URL.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'method' (string), 'url' (string), 'headers' (array, optional), 'body' (array, optional)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $method = strtoupper($config['method'] ?? 'GET');
        $url = $config['url'] ?? '';
        $headers = $config['headers'] ?? [];
        $body = $config['body'] ?? [];

        $this->validateUrl($url);

        $timeout = (int) config('workflow.action_timeout', 30);
        $pendingRequest = Http::withHeaders($headers)->timeout($timeout);

        $options = [];
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && ! empty($body)) {
            $options['json'] = $body;
        }

        $response = $pendingRequest->send($method, $url, $options);

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
        return 'HTTP Request';
    }

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [
            'method' => ['type' => 'select', 'label' => 'HTTP Method', 'options' => ['GET', 'POST', 'PUT', 'DELETE'], 'required' => true],
            'url' => ['type' => 'string', 'label' => 'Request URL', 'required' => true],
            'headers' => ['type' => 'object', 'label' => 'Headers', 'required' => false],
            'body' => ['type' => 'object', 'label' => 'Request Body', 'required' => false],
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
