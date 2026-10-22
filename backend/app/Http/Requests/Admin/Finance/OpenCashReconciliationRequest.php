<?php

namespace App\Http\Requests\Admin\Finance;

use App\DTOs\Finance\CashReconciliationOpenData;
use App\Models\CashReconciliation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Open a cash reconciliation for a provider and a period (market dates, inclusive). The server computes the cash figures.
 */
class OpenCashReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', CashReconciliation::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider_id' => ['required', 'integer', 'exists:provider_profiles,id'],
            'period_from' => ['required', 'date', 'before_or_equal:today'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'provider_id' => (string) __('admin.cash.fields.provider'),
            'period_from' => (string) __('admin.cash.fields.period_from'),
            'period_to' => (string) __('admin.cash.fields.period_to'),
            'notes' => (string) __('admin.cash.fields.notes'),
        ];
    }

    public function toDto(): CashReconciliationOpenData
    {
        return new CashReconciliationOpenData(
            providerId: (int) $this->validated('provider_id'),
            from: CarbonImmutable::parse((string) $this->validated('period_from')),
            to: CarbonImmutable::parse((string) $this->validated('period_to')),
            notes: $this->filled('notes') ? (string) $this->validated('notes') : null,
            idempotencyKey: (string) $this->validated('idempotency_key'),
        );
    }
}
