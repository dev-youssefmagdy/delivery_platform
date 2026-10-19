<?php

namespace App\Http\Requests\Admin\Markets;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroyCurrencyRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $currency = $this->route('currency');

        return $currency instanceof Currency && Gate::allows('delete', $currency);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(false);
    }
}
