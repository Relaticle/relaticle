<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Illuminate\Support\Facades\Http;

class HttpRequestAction extends BaseAction
{
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

        $pendingRequest = Http::withHeaders($headers);

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
}
