<?php

namespace App\Repositories\Eloquent\Booking;

use App\Enums\Catalog\ProofStage;
use App\Enums\Catalog\ProofType;
use App\Models\BookingProof;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function typesFor(int $bookingId, ?ProofStage $stage = null): array
    {
        return array_values(array_map('strval', BookingProof::query()->toBase()
            ->where('booking_id', $bookingId)
            ->when($stage !== null, static fn ($query) => $query->where('stage', $stage?->value))
            ->distinct()->pluck('proof_type')->all()));
    }

    public function countFor(int $bookingId, ProofStage $stage, ProofType $type): int
    {
        return BookingProof::query()->where('booking_id', $bookingId)->where('stage', $stage->value)->where('proof_type', $type->value)->count();
    }

    public function findByHash(int $bookingId, ProofStage $stage, ProofType $type, string $sha256): ?BookingProof
    {
        return BookingProof::query()->where('booking_id', $bookingId)->where('stage', $stage->value)->where('proof_type', $type->value)->where('sha256', $sha256)->first();
    }

    public function findInBooking(int $bookingId, string $uuid): ?BookingProof
    {
        return BookingProof::query()->where('booking_id', $bookingId)->where('uuid', $uuid)->first();
    }

    public function forBooking(int $bookingId): Collection
    {
        return BookingProof::query()->where('booking_id', $bookingId)->orderBy('id')->get();
    }

    public function detachFilesForCustomer(int $customerId): array
    {
        $bookingIds = DB::table('bookings')->where('customer_id', $customerId)->select('id');
        $paths = DB::table('booking_proofs')->whereIn('booking_id', $bookingIds)->whereNotNull('storage_path')->pluck('storage_path')->map(strval(...))->all();

        // Proofs are append-only at model level; this is the one deliberate, audited erasure of the pictures.
        DB::table('booking_proofs')->whereIn('booking_id', $bookingIds)->whereNotNull('storage_path')->update(['storage_path' => null]);

        return array_values($paths);
    }
}
