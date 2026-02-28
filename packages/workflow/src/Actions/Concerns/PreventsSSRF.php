<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions\Concerns;

trait PreventsSSRF
{
    protected function validateUrl(string $url): void
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';

        if (empty($host)) {
            throw new \InvalidArgumentException('URL must have a valid host.');
        }

        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1', '[::1]'];
        if (in_array(strtolower($host), $blockedHosts, true)) {
            throw new \InvalidArgumentException("Requests to private/local addresses are not allowed.");
        }

        $ip = gethostbyname($host);
        if ($ip !== $host && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new \InvalidArgumentException("Requests to private/reserved IP addresses are not allowed.");
        }
    }
}
