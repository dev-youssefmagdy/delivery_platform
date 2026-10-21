<?php

namespace App\Http\Requests\Api\V1\Booking;

use App\DTOs\Booking\BookingListFilterData;
use App\Enums\Booking\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * GET /bookings?status[]=&service_category_id=&from=&to=&in_flight=&per_page=&page=
 */
class ListBookingsRequest extends FormRequest
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
            'status' => ['nullable', 'array', 'max:15'],
            'status.*' => ['string', Rule::enum(BookingStatus::class)],
            'service_category_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'in_flight' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function filters(): BookingListFilterData
    {
        return BookingListFilterData::fromRequest($this);
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 20);
    }
}
