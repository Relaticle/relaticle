<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Routing\Middleware\ValidateSignature as BaseValidateSignature;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Extends Laravel's signed URL middleware with percent-encoding normalization.
 *
 * Email providers (Postmark, SendGrid, Mailgun) may decode percent-encoded
 * characters in URLs during HTML processing (e.g., %40 → @). Since the
 * signature was computed against the encoded form, verification fails.
 *
 * This middleware first tries the standard check, then falls back to
 * re-encoding query values before comparing.
 *
 * @see https://github.com/laravel/framework/issues/42979
 */
final class ValidateSignature extends BaseValidateSignature
{
    /**
     * @param  Request  $request
     * @param  array<int, string>  $args
     */
    public function handle($request, Closure $next, ...$args): Response // @pest-ignore-type
    {
        [$relative, $ignore] = $this->parseArguments($args);

        $absolute = ! $relative;

        if ($request->hasValidSignatureWhileIgnoring($ignore, $absolute)) {
            return $next($request);
        }

        if ($this->hasValidSignatureWithNormalizedEncoding($request, $absolute, $ignore)
            && URL::signatureHasNotExpired($request)) {
            return $next($request);
        }

        throw new InvalidSignatureException;
    }

    /**
     * Re-encode query values to match the percent-encoding used during signing,
     * then verify the signature against the normalized URL.
     *
     * @param  array<int, string>  $ignore
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

            [$key, $value] = $equalsPos !== false
                ? [substr($part, 0, $equalsPos), substr($part, $equalsPos + 1)]
                : [$part, ''];

            if (in_array($key, $ignore, true)) {
                continue;
            }

            $normalized[] = $key.'='.rawurlencode(rawurldecode($value));
        }

        $normalizedQuery = implode('&', $normalized);
        $original = rtrim("{$url}?{$normalizedQuery}", '?');

        $keys = config('app.key');
        $previousKeys = config('app.previous_keys', []);
        $keys = [$keys, ...$previousKeys];

        $signature = (string) $request->query('signature', '');

        return array_any($keys, fn (string $key): bool => hash_equals(hash_hmac('sha256', $original, $key), $signature));
    }
}
