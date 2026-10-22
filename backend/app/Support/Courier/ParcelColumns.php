<?php

namespace App\Support\Courier;

use App\Support\Money\Currency;
use App\Support\Money\Money;

/**
 * The category-specific answers that admin filters need as real, indexed columns (P4.1 step 3): package size and type,
 * fragile flag and declared value. They are copied from the already validated `service_details` (the schema stays the single
 * source of validation) under fixed field keys; a category whose schema has no such field simply leaves them null.
 */
final class ParcelColumns
{
    /**
     * @param array<string, mixed> $details validated service details
     * @return array{parcel_size: string|null, parcel_category: string|null, is_fragile: bool|null, declared_value_minor: int|null}
     */
    public static function from(array $details, Currency $currency): array
    {
        $declared = $details['declared_value'] ?? null;

        return [
            'parcel_size' => isset($details['parcel_size']) ? (string) $details['parcel_size'] : null,
            'parcel_category' => isset($details['parcel_category']) ? (string) $details['parcel_category'] : null,
            'is_fragile' => array_key_exists('fragile', $details) ? (bool) $details['fragile'] : null,
            'declared_value_minor' => is_numeric($declared) ? Money::fromDecimal(number_format((float) $declared, $currency->exponent, '.', ''), $currency)->amount : null,
        ];
    }
}
