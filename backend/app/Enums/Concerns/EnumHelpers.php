<?php

namespace App\Enums\Concerns;

use App\Enums\Contracts\HasLabel;
use BackedEnum;

/**
 * Helpers shared by every backed enum in the application.
 *
 * @mixin BackedEnum
 */
trait EnumHelpers
{
    /**
     * @return list<string|int>
     */
    public static function values(): array
    {
        $values = [];

        foreach (self::cases() as $case) {
            $values[] = $case->value;
        }

        return $values;
    }

    /**
     * Value => label map, ready for `<select>` options.
     *
     * @return array<string|int, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = self::labelOf($case);
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function labels(): array
    {
        return array_values(self::options());
    }

    /**
     * Like tryFrom(), but tolerant of null, empty strings and numeric strings for int-backed enums.
     */
    public static function tryFromValue(string|int|null $value): ?static
    {
        if ($value === null || $value === '') {
            return null;
        }

        foreach (self::cases() as $case) {
            if ((string) $case->value === (string) $value) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Reverse lookup by the label in the current locale.
     */
    public static function fromLabel(string $label): ?static
    {
        foreach (self::cases() as $case) {
            if (self::labelOf($case) === $label) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Comma separated values for Laravel's `in:` rule.
     */
    public static function inRule(): string
    {
        return 'in:'.implode(',', self::values());
    }

    private static function labelOf(BackedEnum $case): string
    {
        return $case instanceof HasLabel ? $case->label() : (string) $case->value;
    }
}
