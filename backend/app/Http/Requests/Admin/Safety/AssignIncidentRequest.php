<?php

namespace App\Http\Requests\Admin\Safety;

/** Assign a case to an officer, or unassign it. */
class AssignIncidentRequest extends IncidentActionRequest
{
    protected function ability(): string
    {
        return 'assign';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + ['admin_id' => ['nullable', 'integer', 'exists:admins,id']];
    }

    public function adminId(): ?int
    {
        return $this->filled('admin_id') ? $this->integer('admin_id') : null;
    }
}
