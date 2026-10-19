<?php

namespace App\DTOs\Admin;

use App\Enums\Common\Locale;
use App\Http\Requests\Admin\Admins\StoreAdminRequest;
use App\Http\Requests\Admin\Admins\UpdateAdminRequest;

/**
 * Validated input for creating/updating an admin user.
 */
final readonly class AdminUserData
{
    /**
     * @param list<string> $roles role names (roles.name)
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public ?Locale $locale,
        public array $roles,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreAdminRequest|UpdateAdminRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        return new self(
            name: trim((string) $data['name']),
            email: mb_strtolower(trim((string) $data['email'])),
            phone: isset($data['phone']) && $data['phone'] !== '' ? (string) $data['phone'] : null,
            locale: Locale::tryFromValue(isset($data['locale']) ? (string) $data['locale'] : null),
            roles: array_values(array_unique(array_map('strval', (array) ($data['roles'] ?? [])))),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * Model attributes (roles and lock_version are handled by the service).
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'locale' => $this->locale?->value,
        ];
    }
}
