<?php

namespace App\Http\Requests\Admin\Policy;

use App\Models\PolicyVersion;
use Illuminate\Support\Facades\Gate;

class UpdatePolicyVersionRequest extends StorePolicyVersionRequest
{
    public function authorize(): bool
    {
        $policy = $this->route('policyVersion');

        return $policy instanceof PolicyVersion && Gate::allows('update', $policy);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
