<?php

namespace App\Http\Requests\Admin\Finance;

use App\DTOs\Finance\CashSettlementData;
use App\Enums\Finance\CashSettlementMethod;
use App\Http\Requests\Admin\ModelActionRequest;
use Illuminate\Validation\Rule;

/**
 * Record the cash commission the provider handed over: amount (minor units), method, receipt reference and a mandatory reason.
 * The service checks the amount against the debt the wallet shows under its lock.
 */
class SettleCashReconciliationRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'settle';
    }

    protected function parameter(): string
    {
        return 'cashReconciliation';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'amount_minor' => ['required', 'integer', 'min:1', 'max:100000000'],
            'method' => ['required', Rule::enum(CashSettlementMethod::class)],
            'reference' => ['required', 'string', 'min:2', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount_minor' => (string) __('admin.cash.fields.settle_amount'),
            'method' => (string) __('admin.cash.fields.method'),
            'reference' => (string) __('admin.cash.fields.reference'),
            'reason' => (string) __('admin.common.reason'),
        ];
    }

    public function toDto(): CashSettlementData
    {
        return new CashSettlementData(
            amountMinor: (int) $this->validated('amount_minor'),
            method: CashSettlementMethod::from((string) $this->validated('method')),
            reference: (string) $this->validated('reference'),
            reason: (string) $this->reason(),
        );
    }
}
