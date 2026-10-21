<?php

namespace App\Repositories\Contracts\Booking;

use App\Enums\Catalog\ProofType;
use App\Models\BookingProof;
use Illuminate\Support\Collection;

interface BookingProofRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function record(array $attributes): BookingProof;

    public function exists(int $bookingId, ProofType $type): bool;

    /**
     * @return list<string> proof type values present on the booking
     */
    public function typesFor(int $bookingId): array;

    /**
     * @return Collection<int, BookingProof>
     */
    public function forBooking(int $bookingId): Collection;
}
