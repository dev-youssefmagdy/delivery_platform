<?php

namespace App\Services\Booking;

use App\Enums\Booking\CancellationReason;
use App\Enums\Common\ActorType;
use App\Models\CancellationPolicy;
use App\Repositories\Contracts\Booking\CancellationPolicyRepositoryInterface;
use Carbon\CarbonImmutable;

/**
 * Finds the cancellation policy that governs a category (and zone) at a moment and turns it into the snapshot a booking
 * stores (Plan I4). With no policy configured the built-in defaults apply: a short free window, no fees, every reason.
 */
final class CancellationPolicyResolver
{
    public function __construct(private readonly CancellationPolicyRepositoryInterface $policies) {}

    /**
     * @return array{policy_id: int|null, snapshot: array<string, mixed>}
     */
    public function resolve(int $serviceCategoryId, ?int $serviceZoneId, ?CarbonImmutable $at = null): array
    {
        $policy = $this->policies->effectiveFor($serviceCategoryId, $serviceZoneId, $at ?? CarbonImmutable::now());

        return $policy instanceof CancellationPolicy
            ? ['policy_id' => $policy->id, 'snapshot' => $policy->snapshot()]
            : ['policy_id' => null, 'snapshot' => self::defaults()];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'id' => null,
            'name' => 'default',
            'free_grace_seconds' => (int) config('booking.cancellation_defaults.free_grace_seconds', 120),
            'free_wait_seconds' => (int) config('booking.cancellation_defaults.free_wait_seconds', 300),
            'waiting_rate_minor' => (int) config('booking.cancellation_defaults.waiting_rate_minor', 0),
            'require_no_show_evidence' => (bool) config('booking.cancellation_defaults.require_no_show_evidence', true),
            'provider_no_show_after_seconds' => (int) config('booking.cancellation_defaults.provider_no_show_after_seconds', 600),
            'rules' => [],
            'customer_reasons' => CancellationReason::valuesForActor(ActorType::Customer),
            'provider_reasons' => CancellationReason::valuesForActor(ActorType::Provider),
        ];
    }
}
