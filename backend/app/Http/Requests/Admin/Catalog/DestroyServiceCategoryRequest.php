<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroyServiceCategoryRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $category = $this->route('serviceCategory');

        return $category instanceof ServiceCategory && Gate::allows('delete', $category);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(false);
    }
}
