<?php

namespace App\Http\Requests\Admin\Booking;

use App\DTOs\Booking\BookingOverrideData;
use App\Enums\Booking\BookingStatus;
use App\Models\Booking;
use App\Services\Booking\BookingOverrideService;
use Illuminate\Validation\Rule;

/**
 * Force a status change: only targets the transition graph allows from the booking's current status (and that
 * operations may force) are accepted; the transition service enforces the guards of SRS §8.3 again.
 */
class ForceBookingTransitionRequest extends BookingOverrideRequest
{
    protected function ability(): string
    {
        return 'forceTransition';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $booking = $this->route('booking');
        $targets = $booking instanceof Booking ? array_map(static fn (BookingStatus $status): string => $status->value, app(BookingOverrideService::class)->forceTargets($booking)) : [];

        return parent::rules() + ['target_status' => ['required', 'string', Rule::in($targets)]];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => (string) __('admin.common.reason'), 'target_status' => (string) __('admin.bookings.fields.target_status')];
    }

    public function toDto(): BookingOverrideData
    {
        return new BookingOverrideData((string) $this->reason(), targetStatus: BookingStatus::from((string) $this->validated('target_status')));
    }
}
