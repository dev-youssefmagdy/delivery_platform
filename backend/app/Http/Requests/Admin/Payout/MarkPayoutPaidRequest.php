<?php

namespace App\Http\Requests\Admin\Payout;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * The transfer was made: the bank / wallet reference is mandatory, a receipt (PDF or image) and a note are optional. The reference
 * and the file are validated before the submit (Precognition) and again by the service.
 */
class MarkPayoutPaidRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'markPaid';
    }

    protected function parameter(): string
    {
        return 'payoutRequest';
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
            'transfer_reference' => ['required', 'string', 'min:3', 'max:120'],
            'evidence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => (string) __('admin.common.reason'), 'transfer_reference' => (string) __('admin.payouts.fields.transfer_reference'), 'evidence' => (string) __('admin.payouts.fields.evidence')];
    }
}
