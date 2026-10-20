<?php

namespace App\DTOs\Identity;

/**
 * What an OTP request tells the client. Identical for every phone number: nothing about the account.
 */
final readonly class OtpIssued
{
    public function __construct(
        public int $expiresInSeconds,
        public int $resendInSeconds,
        public int $length,
    ) {}
}
