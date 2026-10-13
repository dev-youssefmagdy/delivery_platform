<?php

namespace App\Support\Money;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Casts a `*_minor` bigint column plus a char(3) currency column to Money.
 *
 * Usage: `'fare' => MoneyCast::class.':fare_minor,currency'`. When the columns are omitted, the amount column
 * defaults to "{key}_minor" and the currency column to "currency".
 *
 * @implements CastsAttributes<Money|null, mixed>
 */
final class MoneyCast implements CastsAttributes
{
    public function __construct(private readonly ?string $amountColumn = null, private readonly ?string $currencyColumn = null) {}

    /**
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        $amount = $attributes[$this->amountColumn($key)] ?? null;
        $code = $attributes[$this->currencyColumn()] ?? null;

        if ($amount === null || $code === null) {
            return null;
        }

        return new Money((int) $amount, app(CurrencyRegistry::class)->get((string) $code));
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, int|string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$this->amountColumn($key) => null];
        }

        if (! $value instanceof Money) {
            throw new InvalidArgumentException("The [{$key}] attribute must be a Money instance.");
        }

        return [
            $this->amountColumn($key) => $value->amount,
            $this->currencyColumn() => $value->currency->code,
        ];
    }

    private function amountColumn(string $key): string
    {
        return $this->amountColumn ?? "{$key}_minor";
    }

    private function currencyColumn(): string
    {
        return $this->currencyColumn ?? 'currency';
    }
}
