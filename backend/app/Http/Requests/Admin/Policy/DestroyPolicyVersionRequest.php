<?php

namespace App\Http\Requests\Admin\Policy;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\PolicyVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroyPolicyVersionRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $policy = $this->route('policyVersion');

        return $policy instanceof PolicyVersion && Gate::allows('delete', $policy);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(false);
    }
}
