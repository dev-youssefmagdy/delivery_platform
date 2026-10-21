<?php

namespace App\DTOs\Booking;

use App\Support\Geo\GeoPoint;

/**
 * A point of a booking (pickup, drop-off or stop) with the address text the client showed. The address is a hint kept
 * for display and support; the coordinates are what the server uses.
 */
final readonly class BookingLocationData
{
    /**
     * @param array<string, string|null> $address {title, line}
     */
    public function __construct(public GeoPoint $point, public array $address = []) {}

    /**
     * @param array<string, mixed> $input {lat, lng, address?: {title, line}}
     */
    public static function fromInput(array $input): ?self
    {
        $point = GeoPoint::tryMake($input['lat'] ?? null, $input['lng'] ?? null);

        if ($point === null) {
            return null;
        }

        $address = [];

        foreach (['title', 'line'] as $key) {
            $value = $input['address'][$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                $address[$key] = mb_substr(trim($value), 0, (int) config('booking.address_line_max', 255));
            }
        }

        return new self($point, $address);
    }

    /**
     * @return array<string, string|null>|null
     */
    public function addressOrNull(): ?array
    {
        return $this->address === [] ? null : $this->address;
    }
}
