<?php

namespace App\Services\Provider;

use App\Enums\Common\ActorType;
use App\Enums\Provider\ProviderStatus;
use App\Events\ProviderStatusChanged;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderStatusEventRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The ONLY code that changes `provider_profiles.status` (the provider counterpart of ADR-16). It enforces the graph
 * of `ProviderStatus::allowedTransitions()`, writes the status history row and dispatches `ProviderStatusChanged`.
 * Callers hold the profile row lock (inside their transaction) and add the audit entry that fits their action.
 */
final class ProviderStatusService
{
    public function __construct(
        private readonly ProviderProfileRepositoryInterface $profiles,
        private readonly ProviderStatusEventRepositoryInterface $events,
    ) {}

    /**
     * @param array<string, mixed> $meta safe, non-personal facts stored with the history row
     *
     * @throws BusinessRuleViolation `provider_status_transition_invalid`
     */
    public function transition(
        ProviderProfile $profile,
        ProviderStatus $to,
        ?Authenticatable $actor,
        ?string $reason = null,
        ?CarbonInterface $expiresAt = null,
        array $meta = [],
    ): ProviderProfile {
        $from = $profile->status;

        if (! $from->canTransitionTo($to)) {
            throw BusinessRuleViolation::make('provider_status_transition_invalid');
        }

        $this->profiles->applyStatus($profile, $to, $reason, $expiresAt);
        [$actorType, $actorId] = $this->actor($actor);
        $this->events->append($profile, $from, $to, $actorType, $actorId, $reason, $meta);

        ProviderStatusChanged::dispatch($profile, $from, $to, $reason);

        return $profile;
    }

    /**
     * Records the very first status of a new profile (no previous status).
     */
    public function recordCreation(ProviderProfile $profile, ?Authenticatable $actor): void
    {
        [$actorType, $actorId] = $this->actor($actor);
        $this->events->append($profile, null, $profile->status, $actorType, $actorId, null);
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    private function actor(?Authenticatable $actor): array
    {
        return match (true) {
            $actor instanceof Admin => [ActorType::Admin->value, $actor->id],
            $actor instanceof User => [ActorType::Provider->value, $actor->id],
            default => [ActorType::System->value, null],
        };
    }
}
