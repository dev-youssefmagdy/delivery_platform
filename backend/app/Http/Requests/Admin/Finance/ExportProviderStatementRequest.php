<?php

namespace App\Http\Requests\Admin\Finance;

use App\Models\LedgerAccount;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Provider statement export: an optional date range. Needs `finance.view` and `reports.export`.
 */
class ExportProviderStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('exportStatements', LedgerAccount::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    public function from(): ?CarbonImmutable
    {
        return $this->filled('from') ? CarbonImmutable::parse((string) $this->validated('from')) : null;
    }

    public function to(): ?CarbonImmutable
    {
        return $this->filled('to') ? CarbonImmutable::parse((string) $this->validated('to')) : null;
    }
}
