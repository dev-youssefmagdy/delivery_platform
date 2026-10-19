<?php

namespace App\Http\Requests\Admin\Policy;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\PolicyVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Dangerous: publishing makes the text binding for users, so it needs a reason.
 */
class PublishPolicyVersionRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $policy = $this->route('policyVersion');

        return $policy instanceof PolicyVersion && Gate::allows('publish', $policy);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true);
    }
}
