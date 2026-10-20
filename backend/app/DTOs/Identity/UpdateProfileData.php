<?php

namespace App\DTOs\Identity;

/**
 * Only the fields the client actually sent. `user` holds name/email/preferred_locale, `customer` the customer
 * profile fields (ignored for provider tokens).
 */
final readonly class UpdateProfileData
{
    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $customer
     */
    public function __construct(
        public array $user = [],
        public array $customer = [],
    ) {}
}
