<?php

namespace App\DTOs\Admin;

use App\Http\Requests\Admin\Roles\StoreRoleRequest;
use App\Http\Requests\Admin\Roles\UpdateRoleRequest;

/**
 * Validated input for creating/updating a role with its permission set.
 */
final readonly class RoleData
{
    /**
     * @param array{ar: string, en?: string|null} $label
     * @param list<string> $permissions AdminPermission values
     */
    public function __construct(
        public ?string $name,
        public array $label,
        public array $permissions,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreRoleRequest|UpdateRoleRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        return new self(
            name: isset($data['name']) ? (string) $data['name'] : null,
            label: array_filter((array) $data['label'], static fn (mixed $value): bool => $value !== null && $value !== ''),
            permissions: array_values(array_unique(array_map('strval', (array) ($data['permissions'] ?? [])))),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }
}
