<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Dangerous: the reason is mandatory; `confirm_active_bookings` acknowledges bookings in progress (the service
 * rejects the request with `category_has_active_bookings` when it is missing and bookings exist).
 */
class DeactivateServiceCategoryRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $category = $this->route('serviceCategory');

        return $category instanceof ServiceCategory && Gate::allows('deactivate', $category);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true) + ['confirm_active_bookings' => ['nullable', 'boolean']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => (string) __('admin.common.reason'), 'confirm_active_bookings' => (string) __('admin.service_categories.fields.confirm_active_bookings')];
    }
}
