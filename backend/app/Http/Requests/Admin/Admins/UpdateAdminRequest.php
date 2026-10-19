<?php

namespace App\Http\Requests\Admin\Admins;

use App\Models\Admin;
use Illuminate\Support\Facades\Gate;

class UpdateAdminRequest extends StoreAdminRequest
{
    public function authorize(): bool
    {
        $admin = $this->route('admin');

        return $admin instanceof Admin && Gate::allows('update', $admin);
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
