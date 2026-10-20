<?php

namespace App\Services\Customers;

use App\DTOs\Customers\SuspendCustomerData;
use App\Enums\Common\AuditAction;
use App\Enums\Identity\UserStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\User;
use App\Repositories\Contracts\Customers\CustomerRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Account standing of a customer: suspend (optionally until a date), restrict, reactivate, and the automatic end of
 * temporary suspensions. Every change locks the row, checks the transition, writes before/after and the reason to
 * the audit log. `restricted` customers keep using the app but cannot create bookings; `suspended` ones are blocked
 * from every business endpoint (EnsureAccountActive).
 */
final class CustomerAccountService
{
    /**
     * @var list<UserStatus>
     */
    private const CAN_SUSPEND = [UserStatus::Active, UserStatus::Restricted, UserStatus::VerificationRequired];

    /**
     * @var list<UserStatus>
     */
    private const CAN_REACTIVATE = [UserStatus::Suspended, UserStatus::Restricted, UserStatus::VerificationRequired];

    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function suspend(User $customer, SuspendCustomerData $data, ?Authenticatable $actor): User
    {
        if ($data->expiresAt !== null && $data->expiresAt->lessThanOrEqualTo(now())) {
            throw BusinessRuleViolation::make('suspension_expiry_in_past', 'expires_at');
        }

        return $this->db->transaction(function () use ($customer, $data, $actor): User {
            $locked = $this->lock($customer);
            $this->assertTransition($locked, self::CAN_SUSPEND);

            $before = $this->snapshot($locked);
            $this->customers->applyStatus($locked, UserStatus::Suspended, $data->reason, $data->expiresAt);
            $this->audit->record(AuditAction::Suspended, $locked, $actor, $before, $this->snapshot($locked), $data->reason);

            return $locked;
        });
    }

    public function restrict(User $customer, string $reason, ?Authenticatable $actor): User
    {
        return $this->db->transaction(function () use ($customer, $reason, $actor): User {
            $locked = $this->lock($customer);
            $this->assertTransition($locked, [UserStatus::Active]);

            $before = $this->snapshot($locked);
            $this->customers->applyStatus($locked, UserStatus::Restricted, $reason, null);
            $this->audit->record(AuditAction::Restricted, $locked, $actor, $before, $this->snapshot($locked), $reason);

            return $locked;
        });
    }

    public function reactivate(User $customer, string $reason, ?Authenticatable $actor): User
    {
        return $this->db->transaction(function () use ($customer, $reason, $actor): User {
            $locked = $this->lock($customer);
            $this->assertTransition($locked, self::CAN_REACTIVATE);

            $before = $this->snapshot($locked);
            $this->customers->applyStatus($locked, UserStatus::Active, null, null);
            $this->audit->record(AuditAction::Reactivated, $locked, $actor, $before, $this->snapshot($locked), $reason);

            return $locked;
        });
    }

    /**
     * Scheduled job: reactivates every suspension whose end has passed. Idempotent; each customer is re-checked
     * under its own row lock, so a manual reactivation or a new suspension racing with the job is never overwritten.
     *
     * @return int customers reactivated
     */
    public function reactivateExpired(int $limit = 200): int
    {
        $reactivated = 0;

        foreach ($this->customers->suspensionsEndedBefore(now(), $limit) as $candidate) {
            $reactivated += $this->db->transaction(function () use ($candidate): int {
                $locked = $this->customers->lockForUpdate($candidate->id);

                if ($locked === null || $locked->status !== UserStatus::Suspended || $locked->status_expires_at === null || $locked->status_expires_at->isFuture()) {
                    return 0;
                }

                $before = $this->snapshot($locked);
                $this->customers->applyStatus($locked, UserStatus::Active, null, null);
                $this->audit->record(AuditAction::Reactivated, $locked, null, $before, $this->snapshot($locked), (string) __('admin.customers.auto_reactivated_reason'));

                return 1;
            });
        }

        return $reactivated;
    }

    /**
     * @param list<UserStatus> $allowed
     */
    private function assertTransition(User $customer, array $allowed): void
    {
        if ($customer->isAnonymised() || ! in_array($customer->status, $allowed, true)) {
            throw BusinessRuleViolation::make('customer_status_transition_invalid');
        }
    }

    private function lock(User $customer): User
    {
        return $this->customers->lockForUpdate($customer->id) ?? throw (new ModelNotFoundException)->setModel(User::class);
    }

    /**
     * Audit snapshot of the account standing; contact data never goes into the audit log.
     *
     * @return array<string, mixed>
     */
    private function snapshot(User $customer): array
    {
        return [
            'status' => $customer->status->value,
            'status_reason' => $customer->status_reason,
            'status_expires_at' => $customer->status_expires_at?->toIso8601String(),
        ];
    }
}
