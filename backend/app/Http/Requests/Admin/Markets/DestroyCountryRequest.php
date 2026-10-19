<?php

namespace App\Http\Requests\Admin\Markets;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\Country;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroyCountryRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $country = $this->route('country');

        return $country instanceof Country && Gate::allows('delete', $country);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(false);
    }
}
