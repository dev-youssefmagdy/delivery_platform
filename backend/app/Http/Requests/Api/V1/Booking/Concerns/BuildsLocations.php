<?php

namespace App\Http\Requests\Api\V1\Booking\Concerns;

use App\DTOs\Booking\BookingLocationData;
use App\Http\Requests\Api\V1\Concerns\ValidatesCoordinates;
use App\Support\Geo\GeoPoint;
use Illuminate\Validation\Validator;

/**
 * Shared shape of the places of a booking request: `{lat, lng, address: {title, line}}`.
 *
 * @mixin \Illuminate\Foundation\Http\FormRequest
 */
trait BuildsLocations
{
    use ValidatesCoordinates;

    /**
     * @return array<string, mixed>
     */
    protected function locationRules(string $prefix, bool $required): array
    {
        return [
            $prefix => [$required ? 'required' : 'nullable', 'array'],
            "{$prefix}.lat" => [$required ? 'required' : 'required_with:'.$prefix, 'numeric', 'between:-90,90'],
            "{$prefix}.lng" => [$required ? 'required' : 'required_with:'.$prefix, 'numeric', 'between:-180,180'],
            "{$prefix}.address" => ['nullable', 'array'],
            "{$prefix}.address.title" => ['nullable', 'string', 'max:191'],
            "{$prefix}.address.line" => ['nullable', 'string', 'max:'.(int) config('booking.address_line_max', 255)],
        ];
    }

    protected function rejectImpossibleLocation(Validator $validator, string $prefix): void
    {
        $lat = $this->input("{$prefix}.lat");
        $lng = $this->input("{$prefix}.lng");

        if ($lat !== null && $lng !== null && ! $validator->errors()->hasAny(["{$prefix}.lat", "{$prefix}.lng"]) && GeoPoint::tryMake($lat, $lng) === null) {
            $validator->errors()->add("{$prefix}.lat", __('validation.coordinates'));
        }
    }

    protected function location(string $prefix): ?BookingLocationData
    {
        $value = $this->input($prefix);

        return is_array($value) ? BookingLocationData::fromInput($value) : null;
    }

    /**
     * @return list<BookingLocationData>
     */
    protected function stopLocations(): array
    {
        $stops = [];

        foreach ((array) $this->input('stops', []) as $stop) {
            $location = is_array($stop) ? BookingLocationData::fromInput($stop) : null;

            if ($location !== null) {
                $stops[] = $location;
            }
        }

        return $stops;
    }
}
