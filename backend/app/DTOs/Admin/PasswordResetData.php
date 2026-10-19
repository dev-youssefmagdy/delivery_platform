<?php

namespace App\DTOs\Admin;

use SensitiveParameter;

/**
 * Completes a password reset / invitation with the broker token.
 */
final readonly class PasswordResetData
{
    public function __construct(
        public string $email,
        public string $token,
        #[SensitiveParameter] public string $password,
    ) {}
}
