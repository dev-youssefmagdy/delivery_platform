<?php

namespace App\DTOs\Geography;

use App\Enums\Geo\LocationPrecision;
use App\Support\Geo\GeoPoint;

/**
 * One geocoder / places-search answer. `precision` tells clients whether the address is exact; an approximate
 * result must not be shown as an exact address (SRS §6.3).
 */
final readonly class PlaceData
{
    public function __construct(
        public ?string $id,
        public string $title,
        public string $address,
        public GeoPoint $point,
        public LocationPrecision $precision,
        public string $provider,
    ) {}

    /**
     * @return array{id: string|null, title: string, address: string, lat: float, lng: float, precision: string, provider: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'address' => $this->address,
            'lat' => $this->point->toArray()['lat'],
            'lng' => $this->point->toArray()['lng'],
            'precision' => $this->precision->value,
            'provider' => $this->provider,
        ];
    }
}
