<?php

namespace App\Support\Money;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * ISO 4217 currency with its minor-unit exponent (EGP = 2, KWD = 3, JPY = 0) (ADR-04).
 * The authoritative exponent comes from the `currencies` table via CurrencyRegistry.
 */
final readonly class Currency implements JsonSerializable, Stringable
{
    public string $code;

    /**
     * @param array<string, string> $symbols locale => symbol, e.g. ['ar' => 'ج.م', 'en' => 'EGP']
     */
    public function __construct(string $code, public int $exponent = 2, public array $symbols = [])
    {
        $code = strtoupper($code);

        if (preg_match('/^[A-Z]{3}$/', $code) !== 1) {
            throw new InvalidArgumentException("Invalid ISO 4217 currency code [{$code}].");
        }

        if ($exponent < 0 || $exponent > 4) {
            throw new InvalidArgumentException("Unsupported currency exponent [{$exponent}].");
        }

        $this->code = $code;
    }

    /**
     * @param array<string, string> $symbols
     */
    public static function of(string $code, int $exponent = 2, array $symbols = []): self
    {
        return new self($code, $exponent, $symbols);
    }

    /**
     * Number of minor units in one major unit (10^exponent).
     */
    public function subunit(): int
    {
        return 10 ** $this->exponent;
    }

    public function symbol(string $locale): string
    {
        return $this->symbols[$locale] ?? $this->symbols['en'] ?? $this->code;
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function jsonSerialize(): string
    {
        return $this->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
