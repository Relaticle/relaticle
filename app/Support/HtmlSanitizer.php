<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonySanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class HtmlSanitizer
{
    private static ?SymfonySanitizer $sanitizer = null;

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
        return array_map(
            fn (mixed $value): mixed => is_string($value) ? self::sanitize($value) : $value,
            $customFields,
        );
    }

    private static function instance(): SymfonySanitizer
    {
        return self::$sanitizer ??= new SymfonySanitizer(
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
        );
    }
}
