<?php

namespace App\Http\Requests\Admin\Reporting;

use App\DTOs\Reporting\ReportingRange;
use App\Enums\Admin\AdminPermission;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Dashboard and report filters (`filters[range][from|to]`, timezone, currency, city / zone / category). The dashboard form validates
 * with a Precognition call before it loads the widgets: a bad or too long range is shown inline and nothing is requested.
 */
class DashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(AdminPermission::DashboardView->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'filters' => ['sometimes', 'array'],
            'filters.range' => ['sometimes', 'array'],
            'filters.range.from' => ['nullable', 'date_format:Y-m-d'],
            'filters.range.to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:filters.range.from'],
            'filters.timezone' => ['nullable', 'string', 'timezone:all'],
            'filters.currency' => ['nullable', 'string', 'size:3', 'alpha'],
            'filters.city_ids' => ['nullable', 'array', 'max:100'],
            'filters.city_ids.*' => ['integer', 'min:1'],
            'filters.zone_ids' => ['nullable', 'array', 'max:100'],
            'filters.zone_ids.*' => ['integer', 'min:1'],
            'filters.category_ids' => ['nullable', 'array', 'max:100'],
            'filters.category_ids.*' => ['integer', 'min:1'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $from = $this->input('filters.range.from');
            $to = $this->input('filters.range.to');

            if (! is_string($from) || ! is_string($to) || $validator->errors()->hasAny(['filters.range.from', 'filters.range.to'])) {
                return;
            }

            if (CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) + 1 > ReportingRange::MAX_DAYS) {
                $validator->errors()->add('filters.range.to', __('reporting.validation.range_too_long', ['days' => ReportingRange::MAX_DAYS]));
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'filters.range.from' => __('admin.common.from'),
            'filters.range.to' => __('admin.common.to'),
            'filters.timezone' => __('reporting.filters.timezone'),
            'filters.currency' => __('reporting.filters.currency'),
        ];
    }
}
