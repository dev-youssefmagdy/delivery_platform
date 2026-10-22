<?php

namespace App\Http\Requests\Admin\Payments;

use App\Models\PaymentReconciliationRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Run a payment reconciliation now (finance.reconcile). Reports only; the run is audited.
 */
class RunPaymentReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('run', PaymentReconciliationRun::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
