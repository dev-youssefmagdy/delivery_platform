<?php

namespace App\Services\Courier;

use App\Enums\Catalog\ProofType;
use App\Enums\Common\ActorType;
use App\Exceptions\ForbiddenActionException;
use App\Models\Booking;
use App\Models\BookingProof;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Support\Files\PrivateFileStorage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Who may see a booking's proofs (SRS §7.5, §20.4). The customer sees the proofs of their own booking and the assigned
 * provider those of theirs; anybody else gets a 404. The pickup PIN proof is an internal fact and is never listed to the
 * customer. Pictures are streamed only through signed routes behind the owner's token, with private, no-store caching.
 */
final class ProofAccessService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingProofRepositoryInterface $proofs,
        private readonly PrivateFileStorage $storage,
    ) {}

    /**
     * @return array{booking: Booking, proofs: Collection<int, BookingProof>}
     */
    public function listForCustomer(User $customer, string $bookingUuid): array
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return [
            'booking' => $booking,
            'proofs' => $this->proofs->forBooking($booking->id)->reject(static fn (BookingProof $proof): bool => $proof->proof_type === ProofType::PickupPin)->values(),
        ];
    }

    /**
     * @return array{booking: Booking, proofs: Collection<int, BookingProof>}
     */
    public function listForProvider(ProviderProfile $provider, string $bookingUuid): array
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return ['booking' => $booking, 'proofs' => $this->proofs->forBooking($booking->id)];
    }

    public function fileForCustomer(User $customer, string $bookingUuid, string $proofUuid): StreamedResponse
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->stream($booking, $proofUuid, ActorType::Customer);
    }

    public function fileForProvider(ProviderProfile $provider, string $bookingUuid, string $proofUuid): StreamedResponse
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->stream($booking, $proofUuid, ActorType::Provider);
    }

    private function stream(Booking $booking, string $proofUuid, ActorType $viewer): StreamedResponse
    {
        $proof = $this->proofs->findInBooking($booking->id, $proofUuid);

        if (! $proof instanceof BookingProof || $proof->storage_path === null || ! $this->storage->exists($proof->storage_path)) {
            throw (new ModelNotFoundException)->setModel(BookingProof::class);
        }

        if ($viewer === ActorType::Customer && $proof->proof_type === ProofType::PickupPin) {
            throw ForbiddenActionException::make('proof_forbidden');
        }

        return $this->storage->response($proof->storage_path, (string) ($proof->mime ?? 'image/jpeg'), false, $proof->uuid.'.jpg');
    }
}
