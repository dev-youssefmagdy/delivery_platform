<?php

namespace App\DTOs\Settings;

use App\Enums\Settings\SettingInput;
use App\Enums\Settings\SettingKey;

/**
 * One field of the settings screen, derived from SettingKey metadata (the views contain no per-key markup).
 */
final readonly class SettingFieldData
{
    /**
     * @param array<string, string> $options select options (value => label)
     * @param array{0: int, 1: int}|null $bounds
     */
    public function __construct(
        public SettingKey $key,
        public string $name,
        public string $label,
        public string $description,
        public SettingInput $input,
        public mixed $value,
        public array $options = [],
        public ?array $bounds = null,
        public ?string $unit = null,
        public bool $required = true,
    ) {}

    /**
     * Value as shown in a text input (lists become "2, 4, 7").
     */
    public function displayValue(): string
    {
        if (is_array($this->value)) {
            return implode(', ', array_map(static fn (mixed $item): string => rtrim(rtrim(number_format((float) $item, 3, '.', ''), '0'), '.'), $this->value));
        }

        return $this->value === null ? '' : (string) $this->value;
    }
}
