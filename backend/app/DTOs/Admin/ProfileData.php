<?php

namespace App\DTOs\Admin;

use App\Enums\Common\Locale;
use App\Http\Requests\Admin\Security\UpdateProfileRequest;

/**
 * An admin's own editable profile fields.
 */
final readonly class ProfileData
{
    public function __construct(
        public string $name,
        public ?string $phone,
        public ?Locale $locale,
    ) {}

    public static function fromRequest(UpdateProfileRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        return new self(
            name: trim((string) $data['name']),
            phone: isset($data['phone']) && $data['phone'] !== '' ? (string) $data['phone'] : null,
            locale: Locale::tryFromValue(isset($data['locale']) ? (string) $data['locale'] : null),
        );
    }
}
