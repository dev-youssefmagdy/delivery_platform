<?php

namespace App\DTOs\Pricing;

use App\Enums\Pricing\PriceFactor;
use App\Support\Money\Money;

/**
 * One component of a reference price. `label` is only set for surcharges (it is the admin's own wording); the other
 * factors are named by their code so apps localise them.
 */
final readonly class PriceFactorLine
{
    /**
     * @param array<string, string|null>|null $label ar/en wording of a surcharge
     */
    public function __construct(
        public PriceFactor $factor,
        public Money $amount,
        public ?string $quantity = null,
        public ?array $label = null,
    ) {}

    /**
     * @return array{code: string, label: string, amount_minor: int, quantity: string|null}
     */
    public function toArray(): array
    {
        $locale = app()->getLocale();
        $custom = $this->label === null ? null : ($this->label[$locale] ?? $this->label['ar'] ?? null);

        return [
            'code' => $this->factor->value,
            'label' => $custom ?? $this->factor->label(),
            'amount_minor' => $this->amount->amount,
            'quantity' => $this->quantity,
        ];
    }

    /**
     * Locale-independent form stored in the booking snapshot.
     *
     * @return array{code: string, amount_minor: int, quantity: string|null, label: array<string, string|null>|null}
     */
    public function toSnapshot(): array
    {
        return ['code' => $this->factor->value, 'amount_minor' => $this->amount->amount, 'quantity' => $this->quantity, 'label' => $this->label];
    }
}
