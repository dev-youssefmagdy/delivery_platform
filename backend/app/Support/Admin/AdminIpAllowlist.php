<?php

namespace App\Support\Admin;

use Symfony\Component\HttpFoundation\IpUtils;

/**
 * The optional admin IP allowlist (SRS §16.16): a comma / space separated list of IPv4 / IPv6 addresses and CIDR ranges.
 * An empty list means the feature is off. Parsing and matching live here so the setting rule, the middleware and the
 * tests share one definition.
 */
final class AdminIpAllowlist
{
    /**
     * @return list<string>
     */
    public static function parse(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        $items = preg_split('/[\s,;]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique($items));
    }

    public static function isValidEntry(string $entry): bool
    {
        $parts = explode('/', $entry);

        if (count($parts) > 2 || filter_var($parts[0], FILTER_VALIDATE_IP) === false) {
            return false;
        }

        if (isset($parts[1])) {
            $max = str_contains($parts[0], ':') ? 128 : 32;

            return ctype_digit($parts[1]) && (int) $parts[1] <= $max;
        }

        return true;
    }

    /**
     * @param list<string> $entries
     */
    public static function allows(?string $ip, array $entries): bool
    {
        if ($entries === []) {
            return true;
        }

        return $ip !== null && IpUtils::checkIp($ip, $entries);
    }
}
