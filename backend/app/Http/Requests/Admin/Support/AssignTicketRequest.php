<?php

namespace App\Http\Requests\Admin\Support;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Assign a ticket to an agent of its team, or unassign it (empty `admin_id`).
 */
class AssignTicketRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'assign';
    }

    protected function parameter(): string
    {
        return 'ticket';
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

    public function attributes(): array
    {
        return parent::attributes() + ['admin_id' => (string) __('admin.support_tickets.fields.assignee')];
    }

    public function adminId(): ?int
    {
        return $this->filled('admin_id') ? $this->integer('admin_id') : null;
    }
}
