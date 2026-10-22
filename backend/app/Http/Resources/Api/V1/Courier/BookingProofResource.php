<?php

namespace App\Http\Resources\Api\V1\Courier;

use App\Enums\Catalog\ProofType;
use App\Enums\Common\ActorType;
use App\Models\Booking;
use App\Models\BookingProof;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * A proof as the two parties see it. A picture is reachable only through a short-lived signed URL that still needs the
 * owner's token (the route re-checks ownership); the storage path, disk and scan internals are never returned.
 *
 * @property BookingProof $resource
 */
final class BookingProofResource extends JsonResource
{
    public function __construct(BookingProof $proof, private readonly Booking $booking, private readonly ActorType $viewer)
    {
        parent::__construct($proof);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $proof = $this->resource;

        return [
            'id' => $proof->uuid,
            'type' => $proof->proof_type->value,
            'type_label' => $proof->proof_type->label(),
            'stage' => $proof->stage->value,
            'taken_at' => $proof->created_at->toIso8601ZuluString(),
            'details' => $this->details($proof),
            'file' => $proof->storage_path === null ? null : [
                'url' => URL::temporarySignedRoute(
                    $this->viewer === ActorType::Provider ? 'api.v1.provider.bookings.proofs.file' : 'api.v1.bookings.proofs.file',
                    now()->addMinutes((int) config('courier.proof_url_ttl_minutes', 5)),
                    ['booking' => $this->booking->uuid, 'proof' => $proof->uuid],
                ),
                'mime' => $proof->mime,
                'size_bytes' => $proof->size_bytes,
            ],
        ];
    }

    /**
     * Checklist answers and the measured weight are shown; the hash, scan state and actor ids stay server-side.
     *
     * @return array<string, mixed>|null
     */
    private function details(BookingProof $proof): ?array
    {
        $metadata = (array) $proof->metadata;

        return match ($proof->proof_type) {
            ProofType::Checklist => ['items' => $metadata['items'] ?? []],
            ProofType::Weight => array_filter([
                'weight_kg' => $metadata['weight_kg'] ?? null,
                'declared_kg' => $metadata['declared_kg'] ?? null,
                'weight_mismatch' => $metadata['weight_mismatch'] ?? null,
            ], static fn (mixed $value): bool => $value !== null),
            default => null,
        };
    }
}
