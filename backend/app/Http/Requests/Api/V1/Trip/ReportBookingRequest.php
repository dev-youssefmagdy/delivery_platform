<?php

namespace App\Http\Requests\Api\V1\Trip;

use App\DTOs\Booking\ReportData;
use App\Enums\Booking\ReportCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /bookings/{booking}/reports and POST /provider/bookings/{booking}/reports.
 */
class ReportBookingRequest extends FormRequest
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
            'category' => ['required', 'string', Rule::enum(ReportCategory::class)],
            'description' => ['required', 'string', 'min:5', 'max:'.(int) config('booking.trip.report_max_chars', 2000)],
        ];
    }

    public function toDto(): ReportData
    {
        return new ReportData(ReportCategory::from((string) $this->input('category')), (string) $this->input('description'));
    }
}
