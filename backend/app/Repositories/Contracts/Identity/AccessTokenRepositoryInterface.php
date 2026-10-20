<?php

namespace App\Repositories\Contracts\Identity;

use App\Models\User;
use DateTimeInterface;

/**
 * Sanctum personal access tokens of mobile users.
 */
interface AccessTokenRepositoryInterface
{
    /**
     * @param list<string> $abilities
     * @return string the plain-text token (shown to the client once)
     */
    public function issue(User $user, string $name, array $abilities, DateTimeInterface $expiresAt): string;

    /**
     * Deletes the user's tokens with this name (one per app + device), so a device never accumulates tokens.
     */
    public function revokeNamed(User $user, string $name): int;

    /**
     * Deletes the token the request was authenticated with. Returns its name.
     */
    public function revokeCurrent(User $user): ?string;

    public function revokeAll(User $user): int;
}
