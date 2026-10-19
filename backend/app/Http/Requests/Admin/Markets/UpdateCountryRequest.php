<?php

namespace App\Http\Requests\Admin\Markets;

use App\Models\Country;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class UpdateCountryRequest extends StoreCountryRequest
{
    public function authorize(): bool
    {
        $country = $this->route('country');

        return $country instanceof Country && Gate::allows('update', $country);
    }

    /**
     * The ISO code is immutable, so it is neither submitted nor validated.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['iso2']) + [
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
