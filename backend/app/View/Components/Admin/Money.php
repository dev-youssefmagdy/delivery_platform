<?php

namespace App\View\Components\Admin;

use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money as MoneyValue;
use App\Support\Money\MoneyFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Formats money held in integer minor units (ADR-04/ADR-30). Pass a Money value, or `:minor` + `currency`.
 */
class Money extends Component
{
    public ?string $formatted;

    public function __construct(
        ?MoneyValue $value = null,
        ?int $minor = null,
        ?string $currency = null,
        bool $latinDigits = false,
        bool $symbol = true,
    ) {
        if ($value === null && $minor !== null) {
            $value = MoneyValue::of($minor, app(CurrencyRegistry::class)->get($currency ?? (string) config('admin.default_currency')));
        }

        $this->formatted = $value === null ? null : app(MoneyFormatter::class)->format($value, latinDigits: $latinDigits, withSymbol: $symbol);
    }

    public function render(): View
    {
        return view('components.admin.money');
    }
}
