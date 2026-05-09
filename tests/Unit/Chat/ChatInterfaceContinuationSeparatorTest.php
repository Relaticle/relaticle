<?php

declare(strict_types=1);

it('marks the assistant message for a paragraph break after action approve/reject', function (): void {
    $contents = file_get_contents(__DIR__.'/../../../packages/Chat/resources/views/livewire/chat/chat-interface.blade.php');

    // Both branches set the separator.
    expect(substr_count($contents, "_pendingSeparator = '\\n\\n'"))->toBeGreaterThanOrEqual(2);

    // The text-delta handler honours it.
    expect($contents)->toContain('_pendingSeparator');
});
