<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonySanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final readonly class HtmlSanitizer
{
    public static function sanitize(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return self::instance()->sanitize($html);
    }

    /**
     * Sanitize custom field string values within an attributes array.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function sanitizeAttributes(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            if ($key === 'custom_fields' && is_array($value)) {
                $attributes[$key] = self::sanitizeCustomFields($value);
            } elseif (is_string($value)) {
                $attributes[$key] = self::sanitize($value);
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $customFields
     * @return array<string, mixed>
     */
    public static function sanitizeCustomFields(array $customFields): array
    {
        return array_map(static function (mixed $value): mixed {
            if (is_string($value)) {
                return self::sanitize($value);
            }

            if (is_array($value)) {
                return array_map(
                    static fn (mixed $item): mixed => is_string($item) ? self::sanitize($item) : $item,
                    $value,
                );
            }

            return $value;
        }, $customFields);
    }

    private static function instance(): SymfonySanitizer
    {
        return once(fn (): SymfonySanitizer => new SymfonySanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowElement('img', ['src', 'alt', 'title'])
                ->allowElement('a', ['href', 'title', 'target', 'rel'])
                ->allowElement('table', ['class'])
                ->allowElement('th', ['colspan', 'rowspan'])
                ->allowElement('td', ['colspan', 'rowspan'])
                ->allowRelativeLinks()
                ->allowRelativeMedias()
                ->forceHttpsUrls(false)
        ));
    }
}
