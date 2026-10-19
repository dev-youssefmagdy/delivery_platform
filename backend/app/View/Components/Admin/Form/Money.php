<?php

namespace App\View\Components\Admin\Form;

use App\Support\Admin\FieldName;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money as MoneyValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Minor-unit aware money input (ADR-04): the admin types a decimal amount, the form submits integer minor
 * units in `name` (hidden input). The currency exponent comes from the currency registry.
 */
class Money extends Component
{
    public string $id;

    public string $key;

    public int $exponent;

    public string $minor;

    public string $currencyCode;

    public string $symbol;

    public function __construct(
        public string $name,
        public ?string $label = null,
        public ?string $hint = null,
        MoneyValue|int|string|null $value = null,
        ?string $currency = null,
        ?string $id = null,
        public bool $required = false,
    ) {
        $this->key = FieldName::key($name);
        $this->id = $id ?? FieldName::id($name);

        $code = $value instanceof MoneyValue ? $value->currency->code : ($currency ?? (string) config('admin.default_currency'));
        $resolved = app(CurrencyRegistry::class)->get($code);

        $this->currencyCode = $resolved->code;
        $this->exponent = $resolved->exponent;
        $this->symbol = $resolved->symbol(app()->getLocale());

        $current = FieldName::value($name, $value instanceof MoneyValue ? $value->amount : $value);
        $this->minor = is_numeric($current) ? (string) (int) $current : '';
    }

    public function step(): string
    {
        return $this->exponent === 0 ? '1' : '0.'.str_repeat('0', $this->exponent - 1).'1';
    }

    public function render(): View
    {
        return view('components.admin.form.money');
    }
}
