<?php

namespace App\Http\Requests\Admin\Geography;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\City;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ActivateCityRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $city = $this->route('city');

        return $city instanceof City && Gate::allows('activate', $city);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(false);
    }
}
