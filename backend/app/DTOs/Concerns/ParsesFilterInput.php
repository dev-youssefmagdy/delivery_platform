<?php

namespace App\DTOs\Concerns;

use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use ReflectionEnum;
use Throwable;

/**
 * Tolerant readers for `filters[...]` input. Invalid values are ignored (treated as "no filter"), never errors:
 * filters come from shareable URLs and must not break the listing.
 */
trait ParsesFilterInput
{
    /**
     * @return array<string, mixed>
     */
    protected static function filterInput(Request $request): array
    {
        $filters = $request->input('filters', []);

        return is_array($filters) ? $filters : [];
    }

    /**
     * @param array<string, mixed> $input
     */
    protected static function stringFilter(array $input, string $key, int $max = 191): ?string
    {
        $value = $input[$key] ?? null;

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    /**
     * @param array<string, mixed> $input
     */
    protected static function intFilter(array $input, string $key): ?int
    {
        $value = $input[$key] ?? null;

        return is_scalar($value) && preg_match('/^-?\d{1,18}$/', trim((string) $value)) === 1 ? (int) $value : null;
    }

    /**
     * Decimal input (e.g. "12.50") converted to minor units with the given exponent.
     *
     * @param array<string, mixed> $input
     */
    protected static function minorUnitsFilter(array $input, string $key, int $exponent = 2): ?int
    {
        $value = $input[$key] ?? null;

        if (! is_scalar($value) || preg_match('/^\d{1,15}(\.\d{1,4})?$/', trim((string) $value)) !== 1) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', trim((string) $value), 2), 2, '');

        return (int) $whole * (10 ** $exponent) + (int) str_pad(substr($fraction, 0, $exponent), $exponent, '0');
    }

    /**
     * Minor units back to the decimal string used in filter inputs (integer math, no floats).
     */
    protected static function decimalFromMinor(int $minor, int $exponent = 2): string
    {
        if ($exponent === 0) {
            return (string) $minor;
        }

        $sign = $minor < 0 ? '-' : '';
        $digits = str_pad((string) abs($minor), $exponent + 1, '0', STR_PAD_LEFT);

        return $sign.substr($digits, 0, -$exponent).'.'.substr($digits, -$exponent);
    }

    /**
     * "1"/"0"/"true"/"false" → bool; anything else (including "") → null (no filter).
     *
     * @param array<string, mixed> $input
     */
    protected static function boolFilter(array $input, string $key): ?bool
    {
        $value = $input[$key] ?? null;

        return match (true) {
            $value === true, $value === '1', $value === 1, $value === 'true' => true,
            $value === false, $value === '0', $value === 0, $value === 'false' => false,
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $input
     */
    protected static function dateFilter(array $input, string $key): ?CarbonImmutable
    {
        $value = $input[$key] ?? null;

        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Reads `filters[key][from]` / `filters[key][to]` dates.
     *
     * @param array<string, mixed> $input
     * @return array{0: CarbonImmutable|null, 1: CarbonImmutable|null}
     */
    protected static function dateRangeFilter(array $input, string $key): array
    {
        $range = is_array($input[$key] ?? null) ? $input[$key] : [];

        return [self::dateFilter($range, 'from'), self::dateFilter($range, 'to')];
    }

    /**
     * Reads `filters[key][min]` / `filters[key][max]` integers.
     *
     * @param array<string, mixed> $input
     * @return array{0: int|null, 1: int|null}
     */
    protected static function intRangeFilter(array $input, string $key): array
    {
        $range = is_array($input[$key] ?? null) ? $input[$key] : [];

        return [self::intFilter($range, 'min'), self::intFilter($range, 'max')];
    }

    /**
     * Reads a list of enum values (`filters[key][]`, or a single value), dropping unknown values.
     *
     * @template TEnum of BackedEnum
     *
     * @param array<string, mixed> $input
     * @param class-string<TEnum> $enum
     * @return list<TEnum>
     */
    protected static function enumListFilter(array $input, string $key, string $enum): array
    {
        $raw = $input[$key] ?? [];
        $values = is_array($raw) ? $raw : [$raw];
        $intBacked = (string) (new ReflectionEnum($enum))->getBackingType() === 'int';
        $cases = [];

        foreach ($values as $value) {
            if (is_string($value) || is_int($value)) {
                $case = $enum::tryFrom($intBacked ? (int) $value : (string) $value);

                if ($case !== null && ! in_array($case, $cases, true)) {
                    $cases[] = $case;
                }
            }
        }

        return $cases;
    }

    /**
     * Reads a list of positive integer ids (`filters[key][]`, or a single value).
     *
     * @param array<string, mixed> $input
     * @return list<int>
     */
    protected static function idListFilter(array $input, string $key, int $max = 100): array
    {
        $raw = $input[$key] ?? [];
        $values = is_array($raw) ? $raw : [$raw];
        $ids = [];

        foreach (array_slice($values, 0, $max) as $value) {
            if (is_scalar($value) && preg_match('/^[1-9]\d{0,18}$/', (string) $value) === 1) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param list<mixed> $values
     */
    protected static function countActive(array $values): int
    {
        $count = 0;

        foreach ($values as $value) {
            if ($value !== null && $value !== [] && $value !== '') {
                $count++;
            }
        }

        return $count;
    }
}
