<?php

namespace App\Http\Requests\Admin\Markets;

use App\Models\Currency;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class UpdateCurrencyRequest extends StoreCurrencyRequest
{
    public function authorize(): bool
    {
        $currency = $this->route('currency');

        return $currency instanceof Currency && Gate::allows('update', $currency);
    }

    /**
     * The code and the minor-unit exponent are immutable (ADR-04), so they are neither submitted nor validated.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['code', 'exponent']) + [
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
