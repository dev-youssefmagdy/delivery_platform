<?php

namespace App\Support\Auth;

use RuntimeException;

/**
 * The fixed test OTP (SRS §28) must be impossible outside local/testing: any test-OTP setting present in another
 * environment aborts the boot, so a copied `.env` can never silently open a back door.
 */
final class TestOtpGuard
{
    /**
     * @param list<string> $phones
     * @param list<string> $allowedEnvironments
     *
     * @throws RuntimeException
     */
    public static function assertAllowed(bool $enabled, ?string $code, array $phones, string $environment, array $allowedEnvironments): void
    {
        if (in_array($environment, $allowedEnvironments, true)) {
            return;
        }

        if ($enabled || ($code !== null && $code !== '') || $phones !== []) {
            throw new RuntimeException("The fixed test OTP must not be configured in [{$environment}]: unset OTP_TEST_ENABLED, OTP_TEST_CODE and OTP_TEST_PHONES.");
        }
    }
}
