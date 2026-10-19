<?php

namespace App\DTOs\Settings;

use App\Enums\Settings\SettingGroup;
use App\Http\Requests\Admin\Settings\UpdateSettingsRequest;

/**
 * Validated, typed input of one settings group form.
 */
final readonly class SettingsData
{
    /**
     * @param array<string, mixed> $values typed values keyed by SettingKey value
     */
    public function __construct(
        public SettingGroup $group,
        public array $values,
        public ?string $fingerprint = null,
    ) {}

    public static function fromRequest(UpdateSettingsRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        return new self(
            $request->group(),
            (array) ($data['settings'] ?? []),
            isset($data['fingerprint']) ? (string) $data['fingerprint'] : null,
        );
    }
}
