<?php

namespace App\Http\Requests\Admin\Roles;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroyRoleRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role && Gate::allows('delete', $role);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true);
    }
}
