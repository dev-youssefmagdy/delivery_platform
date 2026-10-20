<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\ServiceFamily;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroyServiceFamilyRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $model = $this->route('serviceFamily');

        return $model instanceof ServiceFamily && Gate::allows('delete', $model);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(false);
    }
}
