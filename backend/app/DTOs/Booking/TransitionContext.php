<?php

namespace App\DTOs\Booking;

use App\Enums\Common\ActorType;
use App\Models\Admin;
use App\Models\ProviderProfile;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Who asks for a status change, why, and the facts the guards of `BookingTransitionService` need. Callers state the
 * facts (offer id, PIN verified, proofs complete); the service decides whether they are enough.
 */
final readonly class TransitionContext
{
    /**
     * @param array<string, mixed> $metadata stored on the status event (fee decision, GPS evidence ...); never PII
     */
    public function __construct(
        public ActorType $actorType,
        public ?int $actorId = null,
        public ?string $reason = null,
        public array $metadata = [],
        public ?int $providerId = null,
        public ?int $offerId = null,
        public bool $pinVerified = false,
        public bool $proofsSatisfied = false,
        public ?CarbonImmutable $at = null,
        public ?string $reasonCode = null,
        public ?CancellationDecision $feeDecision = null,
        /** @var array<string, mixed> GPS evidence of an arrival (point, accuracy, distance to pickup) */
        public array $evidence = [],
    ) {}

    /**
     * @param array<string, mixed> $metadata
     */
    public static function system(?string $reason = null, array $metadata = []): self
    {
        return new self(ActorType::System, null, $reason, $metadata);
    }

    public static function customer(User $customer, ?string $reason = null): self
    {
        return new self(ActorType::Customer, $customer->id, $reason);
    }

    public static function provider(ProviderProfile $provider, ?string $reason = null): self
    {
        return new self(ActorType::Provider, $provider->id, $reason);
    }

    public static function admin(Admin $admin, string $reason): self
    {
        return new self(ActorType::Admin, $admin->id, $reason);
    }

    public function now(): CarbonImmutable
    {
        return $this->at ?? CarbonImmutable::now();
    }
}
