<?php

namespace App\Services\Customers;

use App\Enums\Common\AuditAction;
use App\Enums\Identity\DeletionRequestStatus;
use App\Enums\Identity\UserStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\AccountDeletionRequest;
use App\Models\Admin;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingAttachmentRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingContactRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingItemRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingStopRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\DeliveryAttemptRepositoryInterface;
use App\Repositories\Contracts\Booking\RecipientOtpRepositoryInterface;
use App\Repositories\Contracts\Customers\CustomerActivityRepositoryInterface;
use App\Repositories\Contracts\Customers\CustomerRepositoryInterface;
use App\Repositories\Contracts\Identity\AccountDeletionRequestRepositoryInterface;
use App\Repositories\Contracts\Reviews\RatingModerationRepositoryInterface;
use App\Repositories\Contracts\Risk\RiskSignalRepositoryInterface;
use App\Repositories\Contracts\Safety\SafetyIncidentRepositoryInterface;
use App\Repositories\Contracts\Safety\TrustedContactRepositoryInterface;
use App\Repositories\Contracts\Support\BookingMessageRepositoryInterface;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use App\Support\Files\PrivateFileStorage;
use App\Services\Audit\AuditLogger;
use App\Repositories\Contracts\Coupon\CouponAttemptRepositoryInterface;
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
        private readonly CouponAttemptRepositoryInterface $couponAttempts,
        private readonly ProviderDeletionService $providerDeletions,
        private readonly SupportTicketRepositoryInterface $tickets,
        private readonly BookingMessageRepositoryInterface $chat,
        private readonly BookingContactRepositoryInterface $contacts,
        private readonly RecipientOtpRepositoryInterface $recipientOtps,
        private readonly DeliveryAttemptRepositoryInterface $deliveryAttempts,
        private readonly BookingProofRepositoryInterface $proofs,
        private readonly BookingItemRepositoryInterface $items,
        private readonly BookingAttachmentRepositoryInterface $attachments,
        private readonly BookingStopRepositoryInterface $stops,
        private readonly SafetyIncidentRepositoryInterface $safetyIncidents,
        private readonly TrustedContactRepositoryInterface $trustedContacts,
        private readonly RatingModerationRepositoryInterface $ratingModeration,
        private readonly RiskSignalRepositoryInterface $riskSignals,
        private readonly PrivateFileStorage $files,
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
        $supportFiles = [];

        $request = $this->db->transaction(function () use ($customer, $requestUuid, $reason, $actor, &$photoPath, &$supportFiles): AccountDeletionRequest {
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
            // Coupon reservations and redemptions stay (they back ledger entries); the attempt log is unlinked from the person.
            $couponAttemptsScrubbed = $this->couponAttempts->anonymiseForUser($locked->id);
            // Support: ticket subjects, the person's own messages and every evidence file go; ticket rows, status history and
            // staff notes stay for dispute and finance records. Chat: what the person wrote, text and images, is removed.
            $ticketFiles = $this->tickets->scrubOfUser($locked->id);
            $chatFiles = $this->chat->scrubSentBy($locked->id);
            // Parcels (P4.1): the sender / recipient names and phones, the recipient code hashes, the free text of failed attempts and the
            // proof pictures (which may show people) go; custody events, proof rows (type, time, SHA-256) and attempt outcomes stay.
            $contactsScrubbed = $this->contacts->scrubForCustomer($locked->id);
            $this->recipientOtps->purgeForCustomer($locked->id);
            $this->deliveryAttempts->scrubForCustomer($locked->id);
            $proofFiles = $this->proofs->detachFilesForCustomer($locked->id);
            // Moving / freight (P4.2): item notes and free-text items, the contacts of intermediate stops and every photo / video / document
            // (pictures may show people and homes) go; the structured facts (quantities, dimensions, weights, scope) stay for disputes.
            $itemsScrubbed = $this->items->scrubForCustomer($locked->id);
            $stopContactsScrubbed = $this->stops->scrubContactsForCustomer($locked->id);
            $loadFiles = $this->attachments->eraseForCustomer($locked->id);
            // Safety (P5.1): trusted contacts go (action records keep time and outcome only). Cases WITHOUT a legal hold lose the narrative,
            // callback phone, location, the person's own messages and files; a case under legal hold is kept whole (SRS §13.3).
            $contactsPurged = $this->trustedContacts->purgeForUser($locked->id);
            $safety = $this->safetyIncidents->scrubOfUser($locked->id);
            // Reputation (P5.2): the free text of the person's rating reports and appeals goes (decisions stay for the audit trail); the facts of
            // risk signals about the account are replaced by a marker (type, score, status and decision stay).
            $reputation = $this->ratingModeration->scrubOfUser($locked->id);
            $riskScrubbed = $this->riskSignals->scrubOfSubject(\App\Enums\Risk\RiskSubjectType::Customer, $locked->id);
            $supportFiles = [...$ticketFiles, ...$chatFiles, ...$proofFiles, ...$loadFiles, ...$safety['files']];

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
                meta: ['deletion_request' => $request->uuid, 'bookings_scrubbed' => $scrubbedBookings, 'payment_methods_purged' => $methodsPurged, 'coupon_attempts_scrubbed' => $couponAttemptsScrubbed, 'ticket_files_removed' => count($ticketFiles), 'chat_files_removed' => count($chatFiles), 'booking_contacts_scrubbed' => $contactsScrubbed, 'proof_files_removed' => count($proofFiles), 'booking_items_scrubbed' => $itemsScrubbed, 'stop_contacts_scrubbed' => $stopContactsScrubbed, 'load_files_removed' => count($loadFiles), 'trusted_contacts_purged' => $contactsPurged['contacts'], 'safety_incidents_scrubbed' => $safety['scrubbed'], 'safety_incidents_held' => $safety['held'], 'rating_reports_scrubbed' => $reputation['reports'], 'rating_appeals_scrubbed' => $reputation['appeals'], 'risk_signals_scrubbed' => $riskScrubbed],
            );

            return $request;
        });

        $this->files->deleteMany($supportFiles);

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
