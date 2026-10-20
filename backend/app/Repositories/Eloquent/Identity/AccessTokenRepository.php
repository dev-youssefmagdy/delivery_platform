<?php

namespace App\Repositories\Eloquent\Identity;

use App\Models\User;
use App\Repositories\Contracts\Identity\AccessTokenRepositoryInterface;
use DateTimeInterface;
use Laravel\Sanctum\PersonalAccessToken;

final class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function issue(User $user, string $name, array $abilities, DateTimeInterface $expiresAt): string
    {
        return $user->createToken($name, $abilities, $expiresAt)->plainTextToken;
    }

    public function revokeNamed(User $user, string $name): int
    {
        return $user->tokens()->where('name', $name)->delete();
    }

    public function revokeCurrent(User $user): ?string
    {
        return $this->deleteToken($user->currentAccessToken());
    }

    private function deleteToken(?PersonalAccessToken $token): ?string
    {
        if ($token === null) {
            return null;
        }

        $name = $token->name;
        $token->delete();

        return $name;
    }

    public function revokeAll(User $user): int
    {
        return $user->tokens()->delete();
    }
}
