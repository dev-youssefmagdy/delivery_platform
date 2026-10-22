<?php

namespace App\Http\Requests\Admin\Support;

use App\Models\SupportMacro;
use Illuminate\Support\Facades\Gate;

/**
 * Update a macro (optimistic `lock_version`).
 */
class UpdateMacroRequest extends StoreMacroRequest
{
    public function authorize(): bool
    {
        $macro = $this->route('macro');

        return $macro instanceof SupportMacro && Gate::allows('update', $macro);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + ['lock_version' => ['nullable', 'integer', 'min:0']];
    }
}
