<?php

namespace App\Http\Requests\Api\V1\Provider\Concerns;

use App\DTOs\Location\LocationSample;
use App\Enums\Location\LocationSource;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation and mapping of one position sample (`lat`, `lng`, `recorded_at`, optional accuracy/heading/speed/source)
 * found under a given input key, for the online request and the location upload.
 */
trait ReadsLocationSamples
{
    /**
     * @return array<string, mixed>
     */
    protected function sampleRules(string $prefix): array
    {
        return [
            "{$prefix}.lat" => ['required', 'numeric', 'between:-90,90'],
            "{$prefix}.lng" => ['required', 'numeric', 'between:-180,180'],
            "{$prefix}.recorded_at" => ['required', 'date'],
            "{$prefix}.accuracy_m" => ['nullable', 'numeric', 'min:0', 'max:100000'],
            "{$prefix}.heading" => ['nullable', 'numeric', 'min:0', 'max:360'],
            "{$prefix}.speed_mps" => ['nullable', 'numeric', 'min:0', 'max:1000'],
            "{$prefix}.source" => ['nullable', Rule::enum(LocationSource::class)],
        ];
    }

    /**
     * Rejects the (0, 0) "no fix" position, which passes the range rules.
     *
     * @param list<string> $keys input keys of the samples, e.g. `location` or `samples.3`
     */
    protected function rejectNullIsland(Validator $validator, array $keys): void
    {
        foreach ($keys as $key) {
            $lat = $this->input("{$key}.lat");
            $lng = $this->input("{$key}.lng");

            if (is_numeric($lat) && is_numeric($lng) && GeoPoint::tryMake($lat, $lng) === null && abs((float) $lat) <= 90 && abs((float) $lng) <= 180) {
                $validator->errors()->add("{$key}.lat", (string) __('validation.invalid_coordinates'));
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function sampleFrom(array $data): LocationSample
    {
        return new LocationSample(
            point: GeoPoint::make($data['lat'], $data['lng']),
            recordedAt: CarbonImmutable::parse((string) $data['recorded_at'])->utc(),
            accuracyM: isset($data['accuracy_m']) ? (float) $data['accuracy_m'] : null,
            heading: isset($data['heading']) ? (int) round((float) $data['heading']) % 360 : null,
            speedMps: isset($data['speed_mps']) ? (float) $data['speed_mps'] : null,
            source: isset($data['source']) ? LocationSource::from((string) $data['source']) : LocationSource::Gps,
        );
    }
}
