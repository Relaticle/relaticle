<?php

declare(strict_types=1);

namespace Relaticle\Chat\Support;

use App\Models\Team;
use Filament\Facades\Filament;

final class MentionRenderer
{
    /**
     * Transform `@Token_Underscored` substrings into chip anchors when a matching
     * mention metadata entry is provided. The full content is HTML-escaped first;
     * each chip is then injected as escaped-attribute HTML.
     *
     * @param  list<array{type: string, id: string, label: string}>  $mentions
     */
    public static function render(string $content, array $mentions): string
    {
        $escaped = e($content);

        foreach ($mentions as $mention) {
            $token = '@'.str_replace(' ', '_', $mention['label']);
            $tokenEscaped = e($token);

            $chip = sprintf(
                '<a href="%s" class="inline-flex items-center gap-1 rounded-md bg-primary-50 px-1.5 py-0.5 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300 no-underline hover:bg-primary-100 dark:hover:bg-primary-900/50" data-mention-id="%s" data-mention-type="%s">@%s</a>',
                e(self::buildUrl($mention)),
                e($mention['id']),
                e($mention['type']),
                e($mention['label'])
            );

            $escaped = str_replace($tokenEscaped, $chip, $escaped);
        }

        return $escaped;
    }

    /**
     * @param  array{type: string, id: string, label: string}  $mention
     */
    private static function buildUrl(array $mention): string
    {
        try {
            $tenant = Filament::getTenant();
            $tenantSlug = $tenant instanceof Team ? $tenant->slug : '';
        } catch (\Throwable) {
            $tenantSlug = '';
        }

        if ($tenantSlug === '') {
            return '#';
        }

        $base = "/app/{$tenantSlug}";

        return match ($mention['type']) {
            'company' => "{$base}/companies/{$mention['id']}",
            'people' => "{$base}/people/{$mention['id']}",
            'opportunity' => "{$base}/opportunities/{$mention['id']}",
            'task' => "{$base}/tasks/{$mention['id']}",
            'note' => "{$base}/notes/{$mention['id']}",
            default => '#',
        };
    }
}
