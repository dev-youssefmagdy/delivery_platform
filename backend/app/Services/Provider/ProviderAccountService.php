<?php

namespace App\Services\Provider;

use App\DTOs\Provider\SuspendProviderData;
use App\Enums\Common\AuditAction;
use App\Enums\Provider\ProviderStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Standing of an approved provider: suspend (temporarily with an optional end, or permanently), reactivate, and the
 * automatic end of temporary suspensions. A suspension takes the provider offline (the status service clears the
 * online flag) and `ProviderEligibilityService` then reports `provider_suspended`. Reason + audit on every change.
 */
final class ProviderAccountService
{
    public function __construct(
        private readonly ProviderProfileRepositoryInterface $profiles,
        private readonly ProviderStatusService $status,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation `suspension_expiry_in_past`, `provider_status_transition_invalid`
     */
    public function suspend(ProviderProfile $profile, SuspendProviderData $data, ?Authenticatable $actor): ProviderProfile
    {
        if (! $data->permanent && $data->expiresAt !== null && $data->expiresAt->lessThanOrEqualTo(now())) {
            throw BusinessRuleViolation::make('suspension_expiry_in_past', 'expires_at');
        }

        return $this->db->transaction(function () use ($profile, $data, $actor): ProviderProfile {
            $locked = $this->lock($profile);
            $from = $locked->status;
            $to = $data->permanent ? ProviderStatus::PermanentlySuspended : ProviderStatus::TemporarilySuspended;

            $this->status->transition($locked, $to, $actor, $data->reason, $data->permanent ? null : $data->expiresAt);
            $this->audit->record(
                AuditAction::Suspended,
                $locked,
                $actor,
                ['status' => $from->value],
                ['status' => $to->value, 'status_expires_at' => $locked->status_expires_at?->toIso8601String()],
                $data->reason,
            );

            return $locked;
        });
    }

    /**
     * @throws BusinessRuleViolation `provider_status_transition_invalid` (only a temporary suspension can be lifted)
     */
    public function reactivate(ProviderProfile $profile, string $reason, ?Authenticatable $actor): ProviderProfile
    {
        return $this->db->transaction(function () use ($profile, $reason, $actor): ProviderProfile {
            $locked = $this->lock($profile);

            if ($locked->status !== ProviderStatus::TemporarilySuspended) {
                throw BusinessRuleViolation::make('provider_status_transition_invalid');
            }

            $this->status->transition($locked, ProviderStatus::Approved, $actor, null);
            $this->audit->record(AuditAction::Reactivated, $locked, $actor, ['status' => ProviderStatus::TemporarilySuspended->value], ['status' => ProviderStatus::Approved->value], $reason);

            return $locked;
        });
    }

    /**
     * Scheduled job: lifts every temporary suspension whose end has passed. Idempotent; each provider is re-checked
     * under its own row lock, so a manual change racing with the job is never overwritten.
     *
     * @return int providers reactivated
     */
    public function reactivateExpired(int $limit = 200): int
    {
        $reactivated = 0;

        foreach ($this->profiles->suspensionsEndedBefore(now(), $limit) as $candidate) {
            $reactivated += $this->db->transaction(function () use ($candidate): int {
                $locked = $this->profiles->lockForUpdate($candidate->id);

                if ($locked === null || $locked->status !== ProviderStatus::TemporarilySuspended || $locked->status_expires_at === null || $locked->status_expires_at->isFuture()) {
                    return 0;
                }

                $this->status->transition($locked, ProviderStatus::Approved, null, null);
                $this->audit->record(AuditAction::Reactivated, $locked, null, ['status' => ProviderStatus::TemporarilySuspended->value], ['status' => ProviderStatus::Approved->value], (string) __('admin.providers.auto_reactivated_reason'));

                return 1;
            });
        }

        return $reactivated;
    }

    private function lock(ProviderProfile $profile): ProviderProfile
    {
        return $this->profiles->lockForUpdate($profile->id) ?? throw (new ModelNotFoundException)->setModel(ProviderProfile::class);
    }
}
