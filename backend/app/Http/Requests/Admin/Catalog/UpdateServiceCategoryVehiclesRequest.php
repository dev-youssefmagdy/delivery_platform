<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateServiceCategoryVehiclesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('serviceCategory');

        return $category instanceof ServiceCategory && Gate::allows('update', $category);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_category_ids' => ['nullable', 'array', 'max:50'],
            'vehicle_category_ids.*' => ['integer', 'distinct', Rule::exists('vehicle_categories', 'id')],
        ];
    }

    /**
     * @return list<int>
     */
    public function vehicleCategoryIds(): array
    {
        return array_values(array_map('intval', (array) $this->validated('vehicle_category_ids', [])));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['vehicle_category_ids' => (string) __('admin.service_categories.fields.vehicle_categories')];
    }
}
