<?php

namespace App\Http\Requests\Admin\Booking;

use App\DTOs\Booking\BookingOverrideData;
use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Base of the operator override forms: a mandatory reason and the policy ability of the action. The booking comes from
 * the `{booking}` route parameter; whether its status allows the action is decided by the service under the row lock.
 */
abstract class BookingOverrideRequest extends FormRequest
{
    use HasReason;

    /**
     * Policy ability (BookingPolicy) that guards the action.
     */
    abstract protected function ability(): string;

    public function authorize(): bool
    {
        $booking = $this->route('booking');

        return $booking instanceof Booking && Gate::allows($this->ability(), $booking);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true);
    }

    public function toDto(): BookingOverrideData
    {
        return new BookingOverrideData((string) $this->reason());
    }
}
