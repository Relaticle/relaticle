<?php

declare(strict_types=1);

use Relaticle\Chat\Support\MentionRenderer;

mutates(MentionRenderer::class);

it('renders a single mention as a chip anchor', function (): void {
    $html = MentionRenderer::render(
        'Tell me about @Acme_Corp',
        [['type' => 'company', 'id' => '01abc', 'label' => 'Acme Corp']]
    );

    expect($html)->toContain('data-mention-id="01abc"');
    expect($html)->toContain('data-mention-type="company"');
    expect($html)->toContain('@Acme Corp');
});

it('escapes HTML in content even when the mention is rendered as a chip', function (): void {
    $html = MentionRenderer::render(
        '<script>alert(1)</script> @Acme_Corp',
        [['type' => 'company', 'id' => '01abc', 'label' => 'Acme Corp']]
    );

    expect($html)->toContain('&lt;script&gt;');
    expect($html)->not->toContain('<script>alert(1)</script>');
});

it('returns escaped content unchanged when there are no mentions', function (): void {
    $html = MentionRenderer::render('plain @Foo text', []);

    expect($html)->toBe('plain @Foo text');
});

it('handles multiple mentions in the same content', function (): void {
    $html = MentionRenderer::render(
        '@John_Doe loves @Acme_Corp',
        [
            ['type' => 'people', 'id' => '01a', 'label' => 'John Doe'],
            ['type' => 'company', 'id' => '01b', 'label' => 'Acme Corp'],
        ]
    );

    expect($html)->toContain('data-mention-id="01a"');
    expect($html)->toContain('data-mention-id="01b"');
});
