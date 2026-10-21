<?php

namespace App\Http\Requests\Admin\Booking;

use App\DTOs\Booking\BookingOverrideData;
use App\Enums\Booking\OperationsCancellationFee;

/**
 * Cancel by operations: the reason and the explicit fee decision (waive, or apply the booking's own policy).
 */
class CancelBookingByOperationsRequest extends BookingOverrideRequest
{
    protected function ability(): string
    {
        return 'cancelByOperations';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + ['fee' => ['required', OperationsCancellationFee::inRule()]];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => (string) __('admin.common.reason'), 'fee' => (string) __('admin.bookings.fields.fee_decision')];
    }

    public function toDto(): BookingOverrideData
    {
        return new BookingOverrideData((string) $this->reason(), fee: OperationsCancellationFee::from((string) $this->validated('fee')));
    }
}
