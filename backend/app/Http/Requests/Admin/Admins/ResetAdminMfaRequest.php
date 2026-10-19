<?php

namespace App\Http\Requests\Admin\Admins;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Resetting another admin's MFA needs a reason.
 */
class ResetAdminMfaRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $admin = $this->route('admin');

        return $admin instanceof Admin && Gate::allows('resetMfa', $admin);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true);
    }
}
