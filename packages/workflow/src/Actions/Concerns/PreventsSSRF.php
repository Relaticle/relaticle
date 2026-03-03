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

        // Strip brackets from IPv6 addresses for validation
        $cleanHost = trim($host, '[]');

        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1', '[::1]'];
        if (in_array(strtolower($host), $blockedHosts, true) || in_array(strtolower($cleanHost), $blockedHosts, true)) {
            throw new \InvalidArgumentException("Requests to private/local addresses are not allowed.");
        }

        // Check if the host is a raw IP (v4 or v6) and validate directly
        if (filter_var($cleanHost, FILTER_VALIDATE_IP)) {
            $this->validateIpAddress($cleanHost);
            return;
        }

        // Resolve DNS — check both IPv4 and IPv6
        $ipv4 = gethostbyname($host);
        $ipv6Records = dns_get_record($host, DNS_AAAA) ?: [];

        // Validate IPv4 if resolved
        if ($ipv4 !== $host) {
            $this->validateIpAddress($ipv4);
        }

        // Validate IPv6 records
        foreach ($ipv6Records as $record) {
            if (isset($record['ipv6'])) {
                $this->validateIpAddress($record['ipv6']);
            }
        }
    }

    /**
     * Validate a resolved IP address is not private, reserved, or link-local.
     */
    private function validateIpAddress(string $ip): void
    {
        // Block all private and reserved ranges for both IPv4 and IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new \InvalidArgumentException("Requests to private/reserved IP addresses are not allowed.");
        }

        // Block cloud metadata endpoint (169.254.169.254)
        if (str_starts_with($ip, '169.254.')) {
            throw new \InvalidArgumentException("Requests to link-local addresses are not allowed.");
        }
    }
}
