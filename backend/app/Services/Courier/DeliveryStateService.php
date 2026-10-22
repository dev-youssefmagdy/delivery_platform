<?php

namespace App\Services\Courier;

use App\Enums\Booking\BookingStatus;
use App\Enums\Catalog\ProofStage;
use App\Http\Resources\Api\V1\Courier\DeliveryAttemptResource;
use App\Models\Booking;
use App\Models\DeliveryAttempt;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use App\Repositories\Contracts\Booking\DeliveryAttemptRepositoryInterface;

/**
 * The `delivery` block of a booking in the two apps: custody, which proofs the job needs and has, the recipient code state
 * (never the code) and the failed attempts. `null` for services that carry neither custody nor proofs (rides), so a ride
 * response is unchanged.
 */
final class DeliveryStateService
{
    public function __construct(
        private readonly BookingProofRepositoryInterface $proofs,
        private readonly DeliveryAttemptRepositoryInterface $attempts,
        private readonly RecipientOtpService $recipientOtps,
    ) {}

    public function applies(Booking $booking): bool
    {
        return CustodyService::applies($booking) || DeliveryProofService::supported($booking);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(Booking $booking): ?array
    {
        if (! $this->applies($booking)) {
            return null;
        }

        $safety = (array) ($booking->policy_snapshot['safety'] ?? []);
        $present = [];

        foreach (ProofStage::cases() as $stage) {
            $present[$stage->value] = $this->proofs->typesFor($booking->id, $stage);
        }

        $items = $this->attempts->forBooking($booking->id);
        $max = DeliveryAttemptService::maxAttempts();
        $pending = $items->first(static fn (DeliveryAttempt $attempt): bool => $attempt->isPending());
        $stage = DeliveryProofService::stageOf($booking);

        return [
            'custody' => [
                'status' => $booking->custody_status?->value,
                'label' => $booking->custody_status?->label(),
            ],
            'proofs' => [
                'pickup_required' => (bool) ($safety['pickup_proof_required'] ?? false),
                'delivery_required' => array_values(array_map('strval', (array) ($safety['delivery_proof_types'] ?? []))),
                'return_required' => array_values(array_map('strval', (array) config('courier.proofs.return_proof_types', ['photo']))),
                'present' => $present,
                'current_stage' => $stage?->value,
            ],
            'recipient_otp' => $this->recipientOtps->state($booking),
            'attempts' => [
                'count' => $booking->delivery_attempts_count,
                'max' => $max,
                'retry_available' => $booking->delivery_attempts_count < $max,
                'pending' => $pending === null ? null : (new DeliveryAttemptResource($pending))->resolve(),
                'items' => $items->map(static fn (DeliveryAttempt $attempt): array => (new DeliveryAttemptResource($attempt))->resolve())->values()->all(),
            ],
            'awaiting_resolution' => $booking->status === BookingStatus::DeliveryAttemptFailed && $pending !== null,
            'restricted_goods_acknowledged_at' => $booking->restricted_goods_acknowledged_at?->toIso8601ZuluString(),
        ];
    }
}
