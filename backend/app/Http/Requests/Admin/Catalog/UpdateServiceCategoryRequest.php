<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Models\ServiceCategory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class UpdateServiceCategoryRequest extends StoreServiceCategoryRequest
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
        return Arr::except(parent::rules(), ['code']) + ['lock_version' => ['required', 'integer', 'min:0']];
    }
}
