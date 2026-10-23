<?php

namespace App\Http\Requests\Admin\Lead;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead instanceof Lead && Gate::allows('update', $lead);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
            'assigned_admin_id' => ['nullable', 'integer', 'exists:admins,id'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'notes' => (string) __('leads.fields.notes'),
            'assigned_admin_id' => (string) __('leads.fields.owner'),
        ];
    }
}
