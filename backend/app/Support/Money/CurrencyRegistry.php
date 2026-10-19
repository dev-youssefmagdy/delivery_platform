<?php

namespace App\Support\Money;

/**
 * Resolves a currency code to a Currency (exponent + symbols). Backed by the `currencies` table.
 */
interface CurrencyRegistry
{
    /**
     * @throws UnknownCurrencyException
     */
    public function get(string $code): Currency;

    public function has(string $code): bool;

    /**
     * Drops memoised lookups (after currencies were edited in the same process).
     */
    public function flush(): void;
}
