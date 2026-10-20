<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\VehicleCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ActivateVehicleCategoryRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $model = $this->route('vehicleCategory');

        return $model instanceof VehicleCategory && Gate::allows('activate', $model);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(false);
    }
}
