<?php

namespace App\Http\Requests\Admin\Support;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Link a payment or payout to a ticket. Needs `payments.view`; it moves no money.
 */
class LinkTicketFinanceRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'linkFinance';
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
        return parent::rules() + [
            'payment_id' => ['nullable', 'uuid', 'required_without:payout_id'],
            'payout_id' => ['nullable', 'uuid', 'required_without:payment_id'],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + [
            'payment_id' => (string) __('admin.support_tickets.fields.payment'),
            'payout_id' => (string) __('admin.support_tickets.fields.payout'),
        ];
    }
}
