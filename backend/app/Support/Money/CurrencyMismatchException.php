<?php

namespace App\Support\Money;

use InvalidArgumentException;

final class CurrencyMismatchException extends InvalidArgumentException
{
    public static function between(Currency $a, Currency $b): self
    {
        return new self("Currency mismatch: [{$a->code}] vs [{$b->code}].");
    }
}
