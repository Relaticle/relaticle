<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Relaticle\Workflow\Actions\HttpRequestAction;
use Relaticle\Workflow\Actions\SendEmailAction;
use Relaticle\Workflow\Actions\SendWebhookAction;
use Relaticle\Workflow\Facades\Workflow;
use Relaticle\Workflow\Mail\WorkflowNotification;

// SendWebhookAction tests
it('sends a POST request to the configured URL', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $action = new SendWebhookAction();
    $result = $action->execute([
        'url' => 'https://example.com/webhook',
        'payload' => ['event' => 'order.created', 'data' => ['id' => 123]],
    ], []);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://example.com/webhook'
            && $request['event'] === 'order.created';
    });

    expect($result['status_code'])->toBe(200);
    expect($result['success'])->toBeTrue();
});

it('handles webhook failure gracefully', function () {
    Http::fake(['*' => Http::response('Server Error', 500)]);

    $action = new SendWebhookAction();
    $result = $action->execute([
        'url' => 'https://example.com/webhook',
        'payload' => ['test' => true],
    ], []);

    expect($result['status_code'])->toBe(500);
    expect($result['success'])->toBeFalse();
});

// SendEmailAction tests
it('sends an email notification', function () {
    Mail::fake();

    $action = new SendEmailAction();
    $result = $action->execute([
        'to' => 'user@example.com',
        'subject' => 'Workflow Notification',
        'body' => 'Hello from workflow!',
    ], []);

    Mail::assertSent(function (WorkflowNotification $mail) {
        return $mail->hasTo('user@example.com')
            && $mail->subject === 'Workflow Notification';
    });

    expect($result['sent'])->toBeTrue();
    expect($result['to'])->toBe('user@example.com');
});

// HttpRequestAction tests
it('makes an HTTP GET request', function () {
    Http::fake(['https://api.example.com/data' => Http::response(['users' => []], 200)]);

    $action = new HttpRequestAction();
    $result = $action->execute([
        'method' => 'GET',
        'url' => 'https://api.example.com/data',
    ], []);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.example.com/data'
            && $request->method() === 'GET';
    });

    expect($result['status_code'])->toBe(200);
    expect($result['success'])->toBeTrue();
});

it('makes an HTTP POST request with body', function () {
    Http::fake(['*' => Http::response(['created' => true], 201)]);

    $action = new HttpRequestAction();
    $result = $action->execute([
        'method' => 'POST',
        'url' => 'https://api.example.com/create',
        'body' => ['name' => 'Test'],
        'headers' => ['Authorization' => 'Bearer token123'],
    ], []);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer token123')
            && $request['name'] === 'Test';
    });

    expect($result['status_code'])->toBe(201);
});

// SendEmailAction validation tests
it('throws exception for invalid email address in SendEmailAction', function () {
    $action = new \Relaticle\Workflow\Actions\SendEmailAction();
    $action->execute(['to' => 'not-an-email', 'subject' => 'Test', 'body' => 'Body'], []);
})->throws(\InvalidArgumentException::class);

// Label and configSchema tests
it('has correct labels for all built-in actions', function () {
    expect(SendWebhookAction::label())->toBe('Send Webhook');
    expect(SendEmailAction::label())->toBe('Send Email');
    expect(HttpRequestAction::label())->toBe('HTTP Request');
});

it('has correct configSchema for SendWebhookAction', function () {
    $schema = SendWebhookAction::configSchema();

    expect($schema)
        ->toBeArray()
        ->toHaveKey('url')
        ->toHaveKey('payload');

    expect($schema['url'])
        ->toHaveKey('type', 'string')
        ->toHaveKey('required', true);
});

it('has correct configSchema for SendEmailAction', function () {
    $schema = SendEmailAction::configSchema();

    expect($schema)
        ->toBeArray()
        ->toHaveKey('to')
        ->toHaveKey('subject')
        ->toHaveKey('body');

    expect($schema['to'])
        ->toHaveKey('type', 'string')
        ->toHaveKey('required', true);

    expect($schema['subject'])
        ->toHaveKey('type', 'string')
        ->toHaveKey('required', true);
});

it('has correct configSchema for HttpRequestAction', function () {
    $schema = HttpRequestAction::configSchema();

    expect($schema)
        ->toBeArray()
        ->toHaveKey('method')
        ->toHaveKey('url');

    expect($schema['method'])
        ->toHaveKey('type', 'select')
        ->toHaveKey('required', true);

    expect($schema['url'])
        ->toHaveKey('type', 'string')
        ->toHaveKey('required', true);
});

// Registration tests
it('registers send_webhook as a built-in action', function () {
    $actions = Workflow::getRegisteredActions();

    expect($actions)->toHaveKey('send_webhook');
    expect($actions['send_webhook'])->toBe(SendWebhookAction::class);
});

it('registers send_email as a built-in action', function () {
    $actions = Workflow::getRegisteredActions();

    expect($actions)->toHaveKey('send_email');
    expect($actions['send_email'])->toBe(SendEmailAction::class);
});

it('registers http_request as a built-in action', function () {
    $actions = Workflow::getRegisteredActions();

    expect($actions)->toHaveKey('http_request');
    expect($actions['http_request'])->toBe(HttpRequestAction::class);
});
