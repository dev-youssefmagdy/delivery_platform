<?php

namespace App\Http\Requests\Admin\Booking;

use App\DTOs\Booking\BookingOverrideData;
use App\Enums\Booking\DeliveryAttemptResolution;
use Illuminate\Validation\Rule;

/**
 * Resolve the pending failed delivery attempt by operations (bookings.manage): retry, return to sender or escalate, with
 * a mandatory reason. Whether a retry is still possible (attempts left) is decided by the service under the row lock.
 */
class ResolveDeliveryAttemptRequest extends BookingOverrideRequest
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
        return parent::rules() + ['resolution' => ['required', 'string', Rule::in(DeliveryAttemptResolution::values())]];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => (string) __('admin.common.reason'), 'resolution' => (string) __('admin.bookings.fields.resolution')];
    }

    public function toDto(): BookingOverrideData
    {
        return new BookingOverrideData((string) $this->reason(), resolution: DeliveryAttemptResolution::from((string) $this->validated('resolution')));
    }
}
