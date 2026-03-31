<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates signed URLs with percent-encoding normalization.
 *
 * Email clients and browsers may decode percent-encoded characters in URLs
 * (e.g., %40 → @), causing signature verification to fail because the signature
 * was computed against the encoded form. This middleware falls back to a
 * normalized comparison when the standard check fails.
 */
final class ValidateSignature
{
    /**
     * @var array<int, string>
     */
    protected static array $neverValidate = [];

    public function handle(Request $request, Closure $next, string ...$args): Response
    {
        [$relative, $ignore] = $this->parseArguments($args);

        $absolute = ! $relative;

        if ($request->hasValidSignatureWhileIgnoring($ignore, $absolute)) {
            return $next($request);
        }

        if ($this->hasValidSignatureWithNormalizedEncoding($request, $absolute, $ignore)) {
            return $next($request);
        }

        throw new InvalidSignatureException;
    }

    /**
     * @param  array<int, string>|string  $ignore
     */
    public static function relative(array|string $ignore = []): string
    {
        $ignore = Arr::wrap($ignore);

        return self::class.':'.implode(',', empty($ignore) ? ['relative'] : ['relative', ...$ignore]);
    }

    /**
     * @param  array<int, string>|string  $ignore
     */
    public static function absolute(array|string $ignore = []): string
    {
        $ignore = Arr::wrap($ignore);

        return empty($ignore)
            ? self::class
            : self::class.':'.implode(',', $ignore);
    }

    /**
     * @param  array<int, string>|string  $parameters
     */
    public static function except(array|string $parameters): void
    {
        self::$neverValidate = array_values(array_unique(
            array_merge(self::$neverValidate, Arr::wrap($parameters))
        ));
    }

    /**
     * Re-encode query string values to match the percent-encoding used during signing,
     * then verify the signature against the normalized URL.
     *
     * @param  array<int|string, string>  $ignore
     */
    private function hasValidSignatureWithNormalizedEncoding(Request $request, bool $absolute, array $ignore): bool
    {
        $ignore[] = 'signature';

        $url = $absolute ? $request->url() : '/'.$request->path();

        $rawQuery = (string) $request->server->get('QUERY_STRING');

        if ($rawQuery === '') {
            return false;
        }

        $normalized = [];

        foreach (explode('&', $rawQuery) as $part) {
            $equalsPos = strpos($part, '=');

            if ($equalsPos === false) {
                $key = $part;
                $value = '';
            } else {
                $key = substr($part, 0, $equalsPos);
                $value = substr($part, $equalsPos + 1);
            }
            if ($key === 'signature') {
                continue;
            }
            if (in_array($key, $ignore, true)) {
                continue;
            }

            $normalized[] = $key.'='.rawurlencode(rawurldecode($value));
        }

        $normalizedQuery = implode('&', $normalized);
        $original = rtrim("{$url}?{$normalizedQuery}", '?');

        $keys = [config('app.key'), ...config('app.previous_keys', [])];

        $signature = (string) $request->query('signature', '');

        return array_any($keys, fn (string $key): bool => hash_equals(hash_hmac('sha256', $original, $key), $signature));
    }

    /**
     * @param  array<int|string, string>  $args
     * @return array{bool, array<int|string, string>}
     */
    private function parseArguments(array $args): array
    {
        $relative = ! empty($args) && $args[0] === 'relative';

        if ($relative) {
            array_shift($args);
        }

        return [$relative, array_merge($args, self::$neverValidate)];
    }
}
