<?php

namespace Tests\Support;

use App\Enums\Admin\AdminPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * In-memory admin used by feature tests until the real Admin model + guard land in P1.1
 * (then InteractsWithAdmin::actingAsAdmin() switches to Admin::factory() with Spatie permissions).
 */
final class TestAdmin implements Authenticatable, Authorizable
{
    /**
     * @param list<string> $permissions
     */
    public function __construct(public array $permissions = [], public int $id = 1, public string $name = 'Test Admin') {}

    public function can($abilities, $arguments = []): bool
    {
        foreach ((array) $abilities as $ability) {
            $value = $ability instanceof AdminPermission ? $ability->value : (string) $ability;

            if (! in_array($value, $this->permissions, true)) {
                return false;
            }
        }

        return true;
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): string
    {
        return '';
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return '';
    }
}
