<?php

namespace App\Http\Requests\Api\V1\Catalog;

use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /vehicle-categories?service_category_id=
 */
class ListVehicleCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['service_category_id' => ['nullable', 'integer', 'min:1']];
    }

    public function serviceCategoryId(): ?int
    {
        $value = $this->validated('service_category_id');

        return $value === null ? null : (int) $value;
    }
}
