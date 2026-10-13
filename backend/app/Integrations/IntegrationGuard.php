<?php

namespace App\Integrations;

use RuntimeException;

/**
 * Refuses simulated drivers (fake/log) outside local/testing (ADR-12, Session Preamble rule 7).
 */
final class IntegrationGuard
{
    /**
     * @param array<string, string|null> $drivers area => configured driver
     * @param list<string> $nonProductionDrivers
     * @param list<string> $allowedEnvironments
     *
     * @throws RuntimeException
     */
    public static function assertAllowed(array $drivers, string $environment, array $nonProductionDrivers, array $allowedEnvironments): void
    {
        if (in_array($environment, $allowedEnvironments, true)) {
            return;
        }

        $offending = array_filter(
            $drivers,
            static fn (?string $driver): bool => $driver !== null && in_array(strtolower($driver), $nonProductionDrivers, true),
        );

        if ($offending !== []) {
            $list = implode(', ', array_map(
                static fn (string $area, string $driver): string => "{$area}={$driver}",
                array_keys($offending),
                array_values($offending),
            ));

            throw new RuntimeException("Simulated integration drivers are not allowed in [{$environment}]: {$list}.");
        }
    }
}
