<?php

namespace App\Http\Requests\Admin\Booking;

use App\DTOs\Booking\BookingOverrideData;

/**
 * Extend the search window by 30 seconds up to the configured maximum.
 */
class ExtendBookingSearchRequest extends BookingOverrideRequest
{
    protected function ability(): string
    {
        return 'manage';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + ['extend_seconds' => ['required', 'integer', 'min:30', 'max:'.(int) config('booking.operations.extend_search_max_seconds', 1800)]];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => (string) __('admin.common.reason'), 'extend_seconds' => (string) __('admin.bookings.fields.extend_seconds')];
    }

    public function toDto(): BookingOverrideData
    {
        return new BookingOverrideData((string) $this->reason(), extendSeconds: (int) $this->validated('extend_seconds'));
    }
}
