<?php

namespace App\Http\Requests\Admin\Finance;

use App\Models\LedgerReconciliationRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Run a reconciliation now (finance.reconcile). Read-only for the ledger; the run is audited.
 */
class RunReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('run', LedgerReconciliationRun::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
