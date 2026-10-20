<?php

namespace App\DTOs\Identity;

use App\Enums\Identity\ClientApp;
use App\Models\User;
use DateTimeInterface;

final readonly class AuthSession
{
    public function __construct(
        public User $user,
        public string $token,
        public DateTimeInterface $expiresAt,
        public ClientApp $app,
        public bool $isNewUser,
    ) {}
}
