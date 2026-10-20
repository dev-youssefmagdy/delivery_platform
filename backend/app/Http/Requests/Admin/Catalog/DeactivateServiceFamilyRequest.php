<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\ServiceFamily;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeactivateServiceFamilyRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $model = $this->route('serviceFamily');

        return $model instanceof ServiceFamily && Gate::allows('deactivate', $model);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true);
    }
}
