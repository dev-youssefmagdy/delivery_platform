<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Models\VehicleCategory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class UpdateVehicleCategoryRequest extends StoreVehicleCategoryRequest
{
    public function authorize(): bool
    {
        $vehicle = $this->route('vehicleCategory');

        return $vehicle instanceof VehicleCategory && Gate::allows('update', $vehicle);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['code']) + ['lock_version' => ['required', 'integer', 'min:0']];
    }
}
