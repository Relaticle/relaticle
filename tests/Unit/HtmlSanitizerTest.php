<?php

declare(strict_types=1);

use App\Support\HtmlSanitizer;

mutates(HtmlSanitizer::class);

it('strips script tags', function (): void {
    $result = HtmlSanitizer::sanitize('<p>Hello <script>alert(1)</script> world</p>');

    expect($result)
        ->toContain('Hello')
        ->toContain('world')
        ->not->toContain('<script>');
});

it('removes on-event attributes', function (): void {
    $result = HtmlSanitizer::sanitize('<img src="x" onerror="alert(1)">');

    expect($result)->not->toContain('onerror');
});

it('neutralizes javascript protocol in href', function (): void {
    $result = HtmlSanitizer::sanitize('<a href="javascript:alert(1)">click</a>');

    expect($result)->not->toContain('javascript:');
});

it('preserves safe HTML tags', function (): void {
    $html = '<p><strong>Bold</strong> and <em>italic</em></p>';

    expect(HtmlSanitizer::sanitize($html))->toBe($html);
});

it('returns null for null input', function (): void {
    expect(HtmlSanitizer::sanitize(null))->toBeNull();
});

it('returns empty string for empty input', function (): void {
    expect(HtmlSanitizer::sanitize(''))->toBe('');
});

it('sanitizes custom fields within attributes array', function (): void {
    $attributes = [
        'name' => 'Test',
        'custom_fields' => [
            'description' => '<p>Safe</p><script>alert(1)</script>',
            'count' => 42,
        ],
    ];

    $result = HtmlSanitizer::sanitizeAttributes($attributes);

    expect($result['name'])->toBe('Test')
        ->and($result['custom_fields']['description'])->not->toContain('<script>')
        ->and($result['custom_fields']['description'])->toContain('<p>Safe</p>')
        ->and($result['custom_fields']['count'])->toBe(42);
});

it('passes through attributes without custom_fields unchanged', function (): void {
    $attributes = ['name' => 'Test'];

    expect(HtmlSanitizer::sanitizeAttributes($attributes))->toBe($attributes);
});
