<?php

namespace App\Http\Requests\Admin\Finance;

use App\Models\CommissionRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * The "try it" calculator on the rule page: a fare in minor units (and optional tip) to see the split. Read-only.
 */
class PreviewCommissionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rule = $this->route('commissionRule');

        return $rule instanceof CommissionRule && Gate::allows('view', $rule);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fare_minor' => ['required', 'integer', 'min:0', 'max:10000000000'],
            'tip_minor' => ['nullable', 'integer', 'min:0', 'max:10000000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'fare_minor' => __('admin.commission_rules.preview.fare'),
            'tip_minor' => __('admin.commission_rules.preview.tip'),
        ];
    }

    public function fareMinor(): int
    {
        return (int) $this->validated('fare_minor');
    }

    public function tipMinor(): int
    {
        return (int) ($this->validated('tip_minor') ?? 0);
    }
}
