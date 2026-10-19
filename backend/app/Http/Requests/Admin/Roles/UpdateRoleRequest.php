<?php

namespace App\Http\Requests\Admin\Roles;

use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends StoreRoleRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role && Gate::allows('update', $role);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $role = $this->route('role');
        $rules = parent::rules();

        // System role slugs are fixed identifiers: the field is not editable and is ignored.
        if ($role instanceof Role) {
            $rules['name'] = $role->is_system
                ? ['prohibited']
                : ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('roles', 'name')->where('guard_name', 'admin')->ignore($role->getKey())];
        }

        return $rules + ['lock_version' => ['required', 'integer', 'min:0']];
    }
}
