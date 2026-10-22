<?php

namespace App\Services\Customers;

use App\Enums\Common\AuditAction;
use App\Enums\Identity\DeletionRequestStatus;
use App\Enums\Identity\UserStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\AccountDeletionRequest;
use App\Models\Admin;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Customers\CustomerActivityRepositoryInterface;
use App\Repositories\Contracts\Customers\CustomerRepositoryInterface;
use App\Repositories\Contracts\Identity\AccountDeletionRequestRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Payments\PaymentMethodService;
use App\Services\Identity\AccountScrubber;
use App\Services\Provider\ProviderDeletionService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

/**
 * Processing of customer account-deletion requests (SRS §6.2, §13.3).
 *
 * Approving ANONYMISES the account; it never deletes the row, because bookings, payments and ledger entries keep
 * pointing at it (ADR-75 lists exactly what is removed and what is retained, and why). Rejecting restores the status
 * the account had before the request. Both need a reason and are audited.
 */
final class CustomerDeletionService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly AccountDeletionRequestRepositoryInterface $requests,
        private readonly CustomerActivityRepositoryInterface $activity,
        private readonly AccountScrubber $scrubber,
        private readonly BookingRepositoryInterface $bookings,
        private readonly PaymentMethodService $paymentMethods,
        private readonly ProviderDeletionService $providerDeletions,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation `deletion_request_not_pending`, `customer_has_active_bookings`,
     *                               `customer_has_open_financial_items`, `customer_is_provider`
     */
    public function approve(User $customer, string $requestUuid, string $reason, ?Authenticatable $actor): AccountDeletionRequest
    {
        $photoPath = null;

        $request = $this->db->transaction(function () use ($customer, $requestUuid, $reason, $actor, &$photoPath): AccountDeletionRequest {
            $locked = $this->lock($customer);
            $request = $this->pendingRequest($locked, $requestUuid);

            if ($this->activity->activeBookingCount($locked) > 0) {
                throw BusinessRuleViolation::make('customer_has_active_bookings');
            }

            if ($this->activity->openFinancialItemCount($locked) > 0) {
                throw BusinessRuleViolation::make('customer_has_open_financial_items');
            }

            if ($this->customers->hasProviderProfile($locked)) {
                throw BusinessRuleViolation::make('customer_is_provider');
            }

            $previousStatus = $locked->status;
            $scrubbedBookings = $this->bookings->scrubPersonalDataForCustomer($locked->id);
            $photoPath = $this->scrubber->scrub($locked);
            $methodsPurged = $this->paymentMethods->purgeForCustomer($locked);

            $this->requests->update($request, [
                'status' => DeletionRequestStatus::Completed,
                'processed_at' => now(),
                'processed_by_admin_id' => $actor instanceof Admin ? $actor->id : null,
                'processing_note' => $reason,
            ]);

            $this->audit->record(
                AuditAction::Anonymised,
                $locked,
                $actor,
                before: ['status' => $previousStatus->value],
                after: ['status' => UserStatus::Deleted->value, 'anonymised' => true],
                reason: $reason,
                meta: ['deletion_request' => $request->uuid, 'bookings_scrubbed' => $scrubbedBookings, 'payment_methods_purged' => $methodsPurged],
            );

            return $request;
        });

        if ($photoPath !== null) {
            Storage::disk((string) config('identity.photo.disk', 'local'))->delete($photoPath);
        }

        return $request;
    }

    /**
     * @throws BusinessRuleViolation `deletion_request_not_pending`
     */
    public function reject(User $customer, string $requestUuid, string $reason, ?Authenticatable $actor): AccountDeletionRequest
    {
        return $this->db->transaction(function () use ($customer, $requestUuid, $reason, $actor): AccountDeletionRequest {
            $locked = $this->lock($customer);
            $request = $this->pendingRequest($locked, $requestUuid);
            $before = ['status' => $locked->status->value];

            $this->requests->update($request, [
                'status' => DeletionRequestStatus::Rejected,
                'processed_at' => now(),
                'processed_by_admin_id' => $actor instanceof Admin ? $actor->id : null,
                'processing_note' => $reason,
            ]);

            if ($locked->status === UserStatus::DeletionPending) {
                $restored = $request->previous_user_status === UserStatus::DeletionPending ? UserStatus::Active : $request->previous_user_status;
                $this->customers->applyStatus($locked, $restored, $locked->status_reason, $locked->status_expires_at);
            }

            $this->providerDeletions->restoreProvider($locked, $request, $actor);

            $this->audit->record(
                AuditAction::Rejected,
                $locked,
                $actor,
                before: $before,
                after: ['status' => $locked->status->value],
                reason: $reason,
                meta: ['deletion_request' => $request->uuid],
            );

            return $request;
        });
    }

    private function pendingRequest(User $customer, string $uuid): AccountDeletionRequest
    {
        $request = $this->requests->findForUser($customer, $uuid) ?? throw (new ModelNotFoundException)->setModel(AccountDeletionRequest::class);

        if ($request->status !== DeletionRequestStatus::Pending) {
            throw BusinessRuleViolation::make('deletion_request_not_pending');
        }

        return $request;
    }

    private function lock(User $customer): User
    {
        return $this->customers->lockForUpdate($customer->id) ?? throw (new ModelNotFoundException)->setModel(User::class);
    }
}
