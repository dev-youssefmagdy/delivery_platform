<?php

namespace Tests\Support;

use App\Models\Admin;
use PragmaRX\Google2FA\Google2FA;

/**
 * Test helper for TOTP: enables MFA on an admin and produces valid codes.
 */
final class AdminTotp
{
    /**
     * @param list<string> $recoveryCodes plain recovery codes to store (hashed like the service does)
     */
    public static function enable(Admin $admin, array $recoveryCodes = []): string
    {
        $secret = (new Google2FA)->generateSecretKey();

        $admin->forceFill([
            'mfa_secret' => $secret,
            'mfa_enabled_at' => now(),
            'mfa_recovery_codes' => array_map(static fn (string $code): string => hash_hmac('sha256', strtolower($code), (string) config('app.key')), $recoveryCodes),
        ])->save();

        return $secret;
    }

    public static function code(string $secret, int $stepOffset = 0): string
    {
        $google = new Google2FA;

        return $google->oathTotp($secret, $google->getTimestamp() + $stepOffset);
    }
}
