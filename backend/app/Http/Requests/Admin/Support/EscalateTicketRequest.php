<?php

namespace App\Http\Requests\Admin\Support;

use App\Enums\Support\TicketGroup;
use App\Http\Requests\Admin\ModelActionRequest;
use Illuminate\Validation\Rule;

/**
 * Escalate a ticket to finance, safety or operations (reason mandatory, audited).
 */
class EscalateTicketRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'escalate';
    }

    protected function parameter(): string
    {
        return 'ticket';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'group' => ['required', 'string', Rule::in(array_map(static fn (TicketGroup $group): string => $group->value, TicketGroup::escalationTargets()))],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['group' => (string) __('admin.support_tickets.fields.group')];
    }

    public function group(): TicketGroup
    {
        return TicketGroup::from((string) $this->validated('group'));
    }
}
