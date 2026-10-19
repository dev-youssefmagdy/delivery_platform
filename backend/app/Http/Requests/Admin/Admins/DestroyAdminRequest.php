<?php

namespace App\Http\Requests\Admin\Admins;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Deleting (soft) an admin; the reason is optional but recorded.
 */
class DestroyAdminRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $admin = $this->route('admin');

        return $admin instanceof Admin && Gate::allows('delete', $admin);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(false);
    }
}
