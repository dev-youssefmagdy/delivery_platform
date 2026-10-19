<?php

namespace App\DTOs\Admin;

use SensitiveParameter;

/**
 * Credentials of the admin login form.
 */
final readonly class LoginData
{
    public function __construct(
        public string $email,
        #[SensitiveParameter] public string $password,
        public bool $remember = false,
    ) {}
}
