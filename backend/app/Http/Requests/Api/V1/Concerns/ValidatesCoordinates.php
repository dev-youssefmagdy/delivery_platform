<?php

namespace App\Http\Requests\Api\V1\Concerns;

use App\Support\Geo\GeoPoint;
use Illuminate\Validation\Validator;

/**
 * Coordinate fields: range checks come from the rules, impossible pairs (0,0, non-finite) from GeoPoint.
 *
 * @mixin \Illuminate\Foundation\Http\FormRequest
 */
trait ValidatesCoordinates
{
    /**
     * Rules for a `lat` / `lng` pair (optionally prefixed).
     *
     * @return array<string, mixed>
     */
    protected function coordinateRules(string $latKey = 'lat', string $lngKey = 'lng', bool $required = true): array
    {
        $presence = $required ? ['required'] : ['nullable', 'required_with:'.$lngKey];
        $presenceLng = $required ? ['required'] : ['nullable', 'required_with:'.$latKey];

        return [
            $latKey => [...$presence, 'numeric', 'between:-90,90'],
            $lngKey => [...$presenceLng, 'numeric', 'between:-180,180'],
        ];
    }

    protected function rejectImpossiblePoint(Validator $validator, string $latKey = 'lat', string $lngKey = 'lng'): void
    {
        if ($this->filled($latKey) && $this->filled($lngKey) && ! $validator->errors()->hasAny([$latKey, $lngKey])
            && GeoPoint::tryMake($this->input($latKey), $this->input($lngKey)) === null) {
            $validator->errors()->add($latKey, __('validation.coordinates'));
        }
    }

    protected function pointFrom(string $latKey = 'lat', string $lngKey = 'lng'): ?GeoPoint
    {
        return $this->filled($latKey) && $this->filled($lngKey) ? GeoPoint::tryMake($this->input($latKey), $this->input($lngKey)) : null;
    }

    /**
     * Parses a "lat,lng" query value that validation already accepted.
     */
    protected function parsedPoint(string $key): ?GeoPoint
    {
        $value = $this->input($key);

        return is_string($value) ? GeoPoint::parse($value) : null;
    }
}
