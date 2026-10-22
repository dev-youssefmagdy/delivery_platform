<?php

namespace App\Repositories\Contracts\Booking;

use App\Enums\Catalog\ProofStage;
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
     * @return list<string> proof type values present on the booking (of one stage when given)
     */
    public function typesFor(int $bookingId, ?ProofStage $stage = null): array;

    public function countFor(int $bookingId, ProofStage $stage, ProofType $type): int;

    public function findByHash(int $bookingId, ProofStage $stage, ProofType $type, string $sha256): ?BookingProof;

    public function findInBooking(int $bookingId, string $uuid): ?BookingProof;

    /**
     * @return Collection<int, BookingProof>
     */
    public function forBooking(int $bookingId): Collection;

    /**
     * Unlinks the files of the customer's bookings' proofs (account deletion): the rows, types, times and hashes stay as
     * the evidence that a proof existed; the pictures, which may show people, go. Returns the storage paths to delete.
     *
     * @return list<string>
     */
    public function detachFilesForCustomer(int $customerId): array;
}
