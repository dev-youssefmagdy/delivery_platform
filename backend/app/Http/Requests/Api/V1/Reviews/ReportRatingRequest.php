<?php

namespace App\Http\Requests\Api\V1\Reviews;

use App\Enums\Reviews\RatingReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /ratings/{rating}/report: why the person flags a rating written about them, with optional details.
 */
class ReportRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::enum(RatingReportReason::class)],
            'details' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function reason(): RatingReportReason
    {
        return RatingReportReason::from((string) $this->validated('reason'));
    }

    public function details(): ?string
    {
        $details = $this->validated('details');

        return is_string($details) ? $details : null;
    }
}
