<?php

namespace App\DTOs\Booking;

use App\Support\Auth\PhoneNumber;
use App\Support\Geo\GeoPoint;

/**
 * A point of a booking (pickup, drop-off or stop) with the address text the client showed. The address is a hint kept
 * for display and support; the coordinates are what the server uses.
 */
final readonly class BookingLocationData
{
    /**
     * @param array<string, string|null> $address {title, line}
     * @param array{name: string, phone: string, note?: string}|null $contact the person to call at an intermediate stop (P4.2)
     */
    public function __construct(public GeoPoint $point, public array $address = [], public ?array $contact = null) {}

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

        $contact = null;
        $name = trim((string) ($input['contact']['name'] ?? ''));
        $phone = PhoneNumber::normalize(is_string($input['contact']['phone'] ?? null) ? $input['contact']['phone'] : null);

        if ($name !== '' && $phone !== null) {
            $note = trim((string) ($input['contact']['note'] ?? ''));
            $contact = ['name' => mb_substr($name, 0, 100), 'phone' => $phone] + ($note === '' ? [] : ['note' => mb_substr($note, 0, 255)]);
        }

        return new self($point, $address, $contact);
    }

    /**
     * The row written for an intermediate stop.
     *
     * @return array{point: GeoPoint, address: array<string, string|null>|null, contact: array{name: string, phone: string, note?: string}|null}
     */
    public function stopRecord(): array
    {
        return ['point' => $this->point, 'address' => $this->addressOrNull(), 'contact' => $this->contact];
    }

    /**
     * @return array<string, string|null>|null
     */
    public function addressOrNull(): ?array
    {
        return $this->address === [] ? null : $this->address;
    }
}
