<?php

namespace App\Http\Requests\Admin\Support;

use App\Enums\Support\TicketPriority;
use App\Http\Requests\Admin\ModelActionRequest;
use Illuminate\Validation\Rule;

/**
 * Change the priority of an active ticket.
 */
class PriorityTicketRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'changePriority';
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
        return parent::rules() + ['priority' => ['required', 'string', Rule::enum(TicketPriority::class)]];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['priority' => (string) __('admin.support_tickets.fields.priority')];
    }

    public function priority(): TicketPriority
    {
        return TicketPriority::from((string) $this->validated('priority'));
    }
}
