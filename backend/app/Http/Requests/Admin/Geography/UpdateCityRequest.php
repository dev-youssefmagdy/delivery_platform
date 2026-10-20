<?php

namespace App\Http\Requests\Admin\Geography;

use App\Models\City;
use Illuminate\Support\Facades\Gate;

class UpdateCityRequest extends StoreCityRequest
{
    public function authorize(): bool
    {
        $city = $this->route('city');

        return $city instanceof City && Gate::allows('update', $city);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + ['lock_version' => ['required', 'integer', 'min:0']];
    }
}
