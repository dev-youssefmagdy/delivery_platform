<?php

namespace App\Repositories\Eloquent\Booking;

use App\Enums\Catalog\ProofType;
use App\Models\BookingProof;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use Illuminate\Support\Collection;

final class BookingProofRepository implements BookingProofRepositoryInterface
{
    public function record(array $attributes): BookingProof
    {
        return BookingProof::query()->create($attributes);
    }

    public function exists(int $bookingId, ProofType $type): bool
    {
        return BookingProof::query()->where('booking_id', $bookingId)->where('proof_type', $type->value)->exists();
    }

    public function typesFor(int $bookingId): array
    {
        return array_values(array_map('strval', BookingProof::query()->where('booking_id', $bookingId)->distinct()->pluck('proof_type')->all()));
    }

    public function forBooking(int $bookingId): Collection
    {
        return BookingProof::query()->where('booking_id', $bookingId)->orderBy('id')->get();
    }
}
