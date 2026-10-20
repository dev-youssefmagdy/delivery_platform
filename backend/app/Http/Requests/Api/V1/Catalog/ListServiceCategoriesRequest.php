<?php

namespace App\Http\Requests\Api\V1\Catalog;

use App\Enums\Catalog\BookingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * GET /service-categories?lat=&lng=&booking_type=&service_family_id=
 */
class ListServiceCategoriesRequest extends FormRequest
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
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
            'booking_type' => ['nullable', 'string', Rule::enum(BookingType::class)],
            'service_family_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function bookingType(): ?BookingType
    {
        $value = $this->validated('booking_type');

        return is_string($value) ? BookingType::from($value) : null;
    }

    public function familyId(): ?int
    {
        $value = $this->validated('service_family_id');

        return $value === null ? null : (int) $value;
    }
}
