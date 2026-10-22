<?php

namespace App\Http\Requests\Admin\Finance;

use App\DTOs\Finance\AdjustmentRequestData;
use App\Enums\Finance\AdjustmentDirection;
use App\Enums\Finance\LedgerAccountType;
use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\FinancialAdjustmentRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Ask for a wallet adjustment: the wallet (ledger account id from the wallets lookup), the direction, the amount in minor units,
 * a mandatory reason and an optional external reference. A second admin decides; the idempotency key is generated with the form.
 */
class StoreAdjustmentRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        return Gate::allows('create', FinancialAdjustmentRequest::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ledger_account_id' => ['required', 'integer', Rule::exists('ledger_accounts', 'id')->whereIn('type', [LedgerAccountType::CustomerWallet->value, LedgerAccountType::ProviderWallet->value])],
            'direction' => ['required', Rule::enum(AdjustmentDirection::class)],
            'amount_minor' => ['required', 'integer', 'min:1', 'max:100000000'],
            'reference' => ['nullable', 'string', 'max:120'],
            'idempotency_key' => ['required', 'uuid'],
        ] + $this->reasonRules(true);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ledger_account_id' => (string) __('admin.adjustments.fields.wallet'),
            'direction' => (string) __('admin.adjustments.fields.direction'),
            'amount_minor' => (string) __('admin.adjustments.fields.amount'),
            'reference' => (string) __('admin.adjustments.fields.reference'),
            'reason' => (string) __('admin.common.reason'),
        ];
    }

    public function toDto(): AdjustmentRequestData
    {
        return new AdjustmentRequestData(
            accountId: (int) $this->validated('ledger_account_id'),
            direction: AdjustmentDirection::from((string) $this->validated('direction')),
            amountMinor: (int) $this->validated('amount_minor'),
            reason: (string) $this->reason(),
            reference: $this->filled('reference') ? (string) $this->validated('reference') : null,
            idempotencyKey: (string) $this->validated('idempotency_key'),
        );
    }
}
