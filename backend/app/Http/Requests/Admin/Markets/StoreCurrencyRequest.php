<?php

namespace App\Http\Requests\Admin\Markets;

use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Currency::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/', Rule::unique('currencies', 'code')],
            'exponent' => ['required', 'integer', 'between:0,4'],
            'symbol_ar' => ['required', 'string', 'max:16'],
            'symbol_en' => ['required', 'string', 'max:16'],
            'active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => __('admin.currencies.fields.code'),
            'exponent' => __('admin.currencies.fields.exponent'),
            'symbol_ar' => __('admin.currencies.fields.symbol_ar'),
            'symbol_en' => __('admin.currencies.fields.symbol_en'),
            'active' => __('admin.currencies.fields.active'),
        ];
    }
}
