<?php

namespace App\Http\Requests\Api\V1\Trip;

use App\DTOs\Booking\ArrivalData;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /provider/bookings/{booking}/arrived: the GPS fix the app has at the moment of the tap (all optional: the server
 * falls back to the last known position and never blocks an arrival for missing GPS).
 */
class ArrivedRequest extends FormRequest
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
            'accuracy_m' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }

    public function toDto(): ArrivalData
    {
        return new ArrivalData(
            lat: $this->filled('lat') ? (float) $this->input('lat') : null,
            lng: $this->filled('lng') ? (float) $this->input('lng') : null,
            accuracyMeters: $this->filled('accuracy_m') ? (float) $this->input('accuracy_m') : null,
            recordedAt: $this->filled('recorded_at') ? CarbonImmutable::parse((string) $this->input('recorded_at')) : null,
        );
    }
}
