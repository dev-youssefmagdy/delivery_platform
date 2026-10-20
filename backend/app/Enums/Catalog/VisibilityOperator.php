<?php

namespace App\Enums\Catalog;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Comparison operators of a form field's `visible_if` condition. The Flutter renderer mirrors `matches()`.
 */
enum VisibilityOperator: string implements HasLabel
{
    use EnumHelpers;

    case Equals = 'eq';
    case NotEquals = 'neq';
    case In = 'in';
    case NotIn = 'not_in';
    case GreaterThan = 'gt';
    case GreaterOrEqual = 'gte';
    case LessThan = 'lt';
    case LessOrEqual = 'lte';
    case Filled = 'filled';
    case Empty = 'empty';

    public function label(): string
    {
        return __("enums.visibility_operator.{$this->value}");
    }

    public function needsValue(): bool
    {
        return $this !== self::Filled && $this !== self::Empty;
    }

    /**
     * @param mixed $actual the controlling field's current value (null when hidden or unanswered)
     * @param mixed $expected the schema's comparison value (list for in / not_in)
     */
    public function matches(mixed $actual, mixed $expected): bool
    {
        $isFilled = ! ($actual === null || $actual === '' || $actual === []);

        return match ($this) {
            self::Filled => $isFilled,
            self::Empty => ! $isFilled,
            self::Equals => $isFilled && self::loose($actual) === self::loose($expected),
            self::NotEquals => ! $isFilled || self::loose($actual) !== self::loose($expected),
            self::In => $isFilled && self::contains((array) $expected, $actual),
            self::NotIn => ! $isFilled || ! self::contains((array) $expected, $actual),
            self::GreaterThan => $isFilled && is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            self::GreaterOrEqual => $isFilled && is_numeric($actual) && is_numeric($expected) && (float) $actual >= (float) $expected,
            self::LessThan => $isFilled && is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            self::LessOrEqual => $isFilled && is_numeric($actual) && is_numeric($expected) && (float) $actual <= (float) $expected,
        };
    }

    /**
     * Booleans, numbers and strings compare by their string form so "1", 1 and true agree with the JSON schema.
     */
    private static function loose(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? '1' : '0',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value),
        };
    }

    /**
     * @param list<mixed> $haystack
     */
    private static function contains(array $haystack, mixed $actual): bool
    {
        $values = is_array($actual) ? $actual : [$actual];

        foreach ($values as $value) {
            foreach ($haystack as $candidate) {
                if (self::loose($value) === self::loose($candidate)) {
                    return true;
                }
            }
        }

        return false;
    }
}
