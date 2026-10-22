<?php

namespace App\Services\Courier;

use App\DTOs\Courier\ProofUploadData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CustodyStatus;
use App\Enums\Catalog\ProofStage;
use App\Enums\Catalog\ProofType;
use App\Enums\Common\ActorType;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Models\BookingProof;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

/**
 * Proofs the provider adds to a job (SRS §7.5, §20.4): photo, signature, checklist, weight. The server decides the stage
 * from the booking (arrived at pickup = pickup, trip in progress = delivery, on the way back = return), so a pickup photo can
 * never satisfy a delivery requirement. Pictures are inspected, scanned, hashed and kept on the private disk; checklist and
 * weight are validated and stored as metadata. A proof is append-only. `recipient_otp` and `pickup_pin` are not uploadable:
 * they exist only as the result of their own verification.
 */
final class DeliveryProofService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingProofRepositoryInterface $proofs,
        private readonly ProofFileStore $files,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * The stage a proof taken now belongs to, or null when proofs cannot be taken in the booking's state.
     */
    public static function stageOf(Booking $booking): ?ProofStage
    {
        return match ($booking->status) {
            BookingStatus::ProviderArrived => ProofStage::Pickup,
            BookingStatus::InProgress => $booking->custody_status === CustodyStatus::Returning ? ProofStage::Return : ProofStage::Delivery,
            default => null,
        };
    }

    /**
     * The category asks for proofs at all (pickup proof, delivery proofs or a recipient code).
     */
    public static function supported(Booking $booking): bool
    {
        $safety = (array) ($booking->policy_snapshot['safety'] ?? []);

        return (bool) ($safety['pickup_proof_required'] ?? false)
            || (array) ($safety['delivery_proof_types'] ?? []) !== []
            || (bool) ($safety['recipient_otp_required'] ?? false);
    }

    /**
     * @return array{proof: BookingProof, replayed: bool}
     *
     * @throws ConflictException `proof_not_allowed_now`, `proof_already_recorded`, `proof_limit_reached`
     * @throws BusinessRuleViolation `proof_type_not_uploadable`, `proofs_not_supported`, file and field errors
     */
    public function store(ProviderProfile $provider, string $bookingUuid, ProofUploadData $data): array
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        if (! in_array($data->type, [ProofType::Photo, ProofType::Signature, ProofType::Checklist, ProofType::Weight], true)) {
            throw BusinessRuleViolation::make('proof_type_not_uploadable', 'type');
        }

        if (! self::supported($booking)) {
            throw BusinessRuleViolation::make('proofs_not_supported', 'type');
        }

        $stage = self::stageOf($booking) ?? throw ConflictException::make('proof_not_allowed_now', context: ['booking_status' => $booking->status->value]);

        // Slow work (decode, scan, write) happens before the lock; the row is written under it.
        $stored = $this->isPicture($data->type) ? $this->files->store($data->file ?? throw BusinessRuleViolation::make('proof_file_required', 'file'), $booking, $stage) : null;
        $metadata = $this->metadata($booking, $data);

        try {
            $result = $this->db->transaction(function () use ($provider, $booking, $data, $stored, $metadata): array {
                $locked = $this->bookings->lockById($booking->id);

                if (! $locked instanceof Booking || $locked->provider_id !== $provider->id) {
                    throw (new ModelNotFoundException)->setModel(Booking::class);
                }

                // The state may have moved while the file was processed: decide again under the lock.
                $lockedStage = self::stageOf($locked) ?? throw ConflictException::make('proof_not_allowed_now', context: ['booking_status' => $locked->status->value]);

                if ($stored !== null) {
                    $existing = $this->proofs->findByHash($locked->id, $lockedStage, $data->type, $stored->sha256);

                    if ($existing instanceof BookingProof) {
                        return [$existing, true, false];
                    }
                }

                $count = $this->proofs->countFor($locked->id, $lockedStage, $data->type);

                if ($data->type === ProofType::Photo) {
                    if ($count >= (int) config('courier.proofs.max_photos_per_stage', 5)) {
                        throw ConflictException::make('proof_limit_reached', context: ['max' => (int) config('courier.proofs.max_photos_per_stage', 5)]);
                    }
                } elseif ($count > 0) {
                    throw ConflictException::make('proof_already_recorded', context: ['type' => $data->type->value]);
                }

                $proof = $this->proofs->record([
                    'booking_id' => $locked->id,
                    'proof_type' => $data->type,
                    'stage' => $lockedStage,
                    'actor_type' => ActorType::Provider->value,
                    'actor_id' => $provider->id,
                    'storage_disk' => $stored?->disk,
                    'storage_path' => $stored?->path,
                    'sha256' => $stored?->sha256,
                    'mime' => $stored?->mime,
                    'size_bytes' => $stored?->sizeBytes,
                    'scan_status' => $stored?->scanStatus->value,
                    'metadata' => $metadata === [] ? null : $metadata,
                ]);

                return [$proof, false, true];
            });
        } catch (Throwable $e) {
            if ($stored !== null) {
                $this->files->discard($stored);
            }

            throw $e;
        }

        [$proof, $replayed, $written] = $result;

        // A replay of the same picture keeps the file that is already stored; the duplicate copy is removed.
        if ($stored !== null && ! $written) {
            $this->files->discard($stored);
        }

        return ['proof' => $proof, 'replayed' => $replayed];
    }

    /**
     * Records a picture taken as the evidence of a failed attempt, in the caller's transaction (file already stored).
     *
     * @param array<string, mixed> $metadata
     */
    public function recordEvidence(Booking $locked, ProviderProfile $provider, StoredProofFile $file, array $metadata): BookingProof
    {
        return $this->proofs->record([
            'booking_id' => $locked->id,
            'proof_type' => ProofType::Photo,
            'stage' => ProofStage::Delivery,
            'actor_type' => ActorType::Provider->value,
            'actor_id' => $provider->id,
            'storage_disk' => $file->disk,
            'storage_path' => $file->path,
            'sha256' => $file->sha256,
            'mime' => $file->mime,
            'size_bytes' => $file->sizeBytes,
            'scan_status' => $file->scanStatus->value,
            'metadata' => $metadata,
        ]);
    }

    private function isPicture(ProofType $type): bool
    {
        return $type === ProofType::Photo || $type === ProofType::Signature;
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(Booking $booking, ProofUploadData $data): array
    {
        if ($data->type === ProofType::Checklist) {
            $allowed = (array) config('courier.proofs.checklist_items', []);
            $items = [];

            foreach ($data->checklist as $item) {
                if (! in_array($item['code'], $allowed, true) || isset($items[$item['code']])) {
                    throw BusinessRuleViolation::make('proof_checklist_invalid', 'items');
                }

                $items[$item['code']] = $item['checked'];
            }

            if ($items === []) {
                throw BusinessRuleViolation::make('proof_checklist_invalid', 'items');
            }

            return ['items' => $items];
        }

        if ($data->type === ProofType::Weight) {
            $measured = (float) ($data->weightKg ?? throw BusinessRuleViolation::make('proof_weight_required', 'weight_kg'));
            $max = (float) ($booking->policy_snapshot['limits']['max_weight_kg'] ?? 0);

            if ($measured <= 0 || ($max > 0 && $measured > $max * 2)) {
                throw BusinessRuleViolation::make('proof_weight_invalid', 'weight_kg');
            }

            $declared = $booking->cargo_weight_kg === null ? null : (float) $booking->cargo_weight_kg;
            $tolerance = (float) config('courier.proofs.weight_tolerance_percent', 25) / 100;
            $mismatch = $declared !== null && $declared > 0 && abs($measured - $declared) / $declared > $tolerance;

            // Never blocks the job: a mismatch is a fact for support and for a fare adjustment, not a refusal.
            return ['weight_kg' => number_format($measured, 2, '.', ''), 'declared_kg' => $declared === null ? null : number_format($declared, 2, '.', ''), 'weight_mismatch' => $mismatch];
        }

        return $data->note === null ? [] : ['note' => mb_substr($data->note, 0, 200)];
    }
}
