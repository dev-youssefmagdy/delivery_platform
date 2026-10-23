<?php

namespace App\Services\Provider;

use App\Enums\Common\AuditAction;
use App\Enums\Identity\DeletionRequestStatus;
use App\Enums\Identity\UserStatus;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\VehicleStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\AccountDeletionRequest;
use App\Models\Admin;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Customers\CustomerActivityRepositoryInterface;
use App\Repositories\Contracts\Identity\AccountDeletionRequestRepositoryInterface;
use App\Repositories\Contracts\Identity\UserRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderActivityRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderDocumentRepositoryInterface;
use App\Repositories\Contracts\Payout\ProviderPayoutMethodRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleDocumentRepositoryInterface;
use App\Repositories\Contracts\Provider\VehiclePhotoRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Repositories\Contracts\Safety\SafetyIncidentRepositoryInterface;
use App\Repositories\Contracts\Safety\TrustedContactRepositoryInterface;
use App\Repositories\Contracts\Support\BookingMessageRepositoryInterface;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Identity\AccountScrubber;
use App\Support\Files\PrivateFileStorage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

/**
 * Account deletion for accounts that are providers (ADR-77, the provider side of ADR-75).
 *
 * A deletion request (from either app) puts the provider profile in `deletion_pending` and remembers its previous
 * status; a cancelled or rejected request restores it. Approving (a Providers-module action, `customers.process_deletion`)
 * ANONYMISES the account: identity and vehicle documents, their numbers and the vehicle photos are deleted, the birth
 * date and city are cleared and the account is scrubbed like a customer's. The review history (status, reviewer,
 * dates), the vehicles' make, model and plate (referenced by completed jobs and safety records) and the provider's
 * aggregates are retained, with no link back to a person. Approval is refused while the provider has active jobs or
 * unsettled money (`ProviderActivityRepositoryInterface`, a seam that fails loudly once those tables exist).
 */
final class ProviderDeletionService
{
    public function __construct(
        private readonly ProviderProfileRepositoryInterface $profiles,
        private readonly AccountDeletionRequestRepositoryInterface $requests,
        private readonly UserRepositoryInterface $users,
        private readonly ProviderStatusService $status,
        private readonly ProviderActivityRepositoryInterface $activity,
        private readonly CustomerActivityRepositoryInterface $customerActivity,
        private readonly ProviderDocumentRepositoryInterface $providerDocuments,
        private readonly VehicleDocumentRepositoryInterface $vehicleDocuments,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly VehiclePhotoRepositoryInterface $photos,
        private readonly AccountScrubber $scrubber,
        private readonly ProviderPayoutMethodRepositoryInterface $payoutMethods,
        private readonly SupportTicketRepositoryInterface $tickets,
        private readonly BookingMessageRepositoryInterface $chat,
        private readonly SafetyIncidentRepositoryInterface $safetyIncidents,
        private readonly TrustedContactRepositoryInterface $trustedContacts,
        private readonly PrivateFileStorage $storage,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Called inside the transaction that creates a deletion request: the provider profile follows the account.
     */
    public function markProviderPending(User $locked, AccountDeletionRequest $request): void
    {
        $profile = $this->lockedProfile($locked);

        if ($profile === null || $profile->status === ProviderStatus::DeletionPending) {
            return;
        }

        $this->requests->update($request, ['previous_provider_status' => $profile->status]);
        $this->status->transition($profile, ProviderStatus::DeletionPending, $locked);
    }

    /**
     * Called inside the transaction that cancels or rejects a request: the provider returns to its previous status.
     */
    public function restoreProvider(User $locked, AccountDeletionRequest $request, ?Authenticatable $actor): void
    {
        $profile = $this->lockedProfile($locked);

        if ($profile === null || $profile->status !== ProviderStatus::DeletionPending || $request->previous_provider_status === null) {
            return;
        }

        $this->status->transition($profile, $request->previous_provider_status, $actor);
    }

    /**
     * @throws BusinessRuleViolation `deletion_request_not_pending`, `provider_has_active_jobs`, `provider_has_open_financial_items`,
     *                               `customer_has_active_bookings`, `customer_has_open_financial_items`
     */
    public function approve(ProviderProfile $profile, string $requestUuid, string $reason, Admin $actor): AccountDeletionRequest
    {
        $paths = [];

        $request = $this->db->transaction(function () use ($profile, $requestUuid, $reason, $actor, &$paths): AccountDeletionRequest {
            $locked = $this->profiles->lockForUpdate($profile->id) ?? throw (new ModelNotFoundException)->setModel(ProviderProfile::class);
            $user = $this->users->lockById($locked->user_id);

            if (! $user instanceof User) {
                throw (new ModelNotFoundException)->setModel(User::class);
            }

            $request = $this->pendingRequest($user, $requestUuid);

            if ($this->activity->activeJobCount($locked) > 0) {
                throw BusinessRuleViolation::make('provider_has_active_jobs');
            }

            if ($this->activity->openFinancialItemCount($locked) > 0) {
                throw BusinessRuleViolation::make('provider_has_open_financial_items');
            }

            if ($this->customerActivity->activeBookingCount($user) > 0) {
                throw BusinessRuleViolation::make('customer_has_active_bookings');
            }

            if ($this->customerActivity->openFinancialItemCount($user) > 0) {
                throw BusinessRuleViolation::make('customer_has_open_financial_items');
            }

            $paths = $this->removeFiles($locked);
            $this->profiles->scrubPersonalData($locked);
            // Encrypted payout destinations go with the account; payouts keep only their masked snapshot and their ledger entries stay.
            $payoutMethodsPurged = $this->payoutMethods->purgeForProvider($locked->id);
            // Support and chat (P3.7 gap closed in P4.4): ticket subjects, the driver's own ticket messages and evidence files, and every
            // chat message they sent (text and image) are removed, exactly like the customer path; ticket rows and staff notes stay.
            $ticketFiles = $this->tickets->scrubOfUser($user->id);
            $chatFiles = $this->chat->scrubSentBy($user->id);
            // Safety (P5.1): same rule as customers, a legal hold keeps the case whole.
            $this->trustedContacts->purgeForUser($user->id);
            $safety = $this->safetyIncidents->scrubOfUser($user->id);
            $paths = [...$paths, ...$ticketFiles, ...$chatFiles, ...$safety['files']];
            $paths[] = $this->scrubber->scrub($user);

            $this->requests->update($request, [
                'status' => DeletionRequestStatus::Completed,
                'processed_at' => now(),
                'processed_by_admin_id' => $actor->id,
                'processing_note' => $reason,
            ]);

            $this->audit->record(
                AuditAction::Anonymised,
                $locked,
                $actor,
                before: ['status' => $request->previous_provider_status->value ?? $locked->status->value],
                after: ['status' => UserStatus::Deleted->value, 'anonymised' => true],
                reason: $reason,
                meta: ['deletion_request' => $request->uuid, 'payout_methods_purged' => $payoutMethodsPurged, 'ticket_files_removed' => count($ticketFiles), 'chat_files_removed' => count($chatFiles)],
            );

            return $request;
        });

        foreach ($paths as $path) {
            if ($path !== null) {
                $this->storage->delete($path);
                Storage::disk((string) config('identity.photo.disk', 'local'))->delete($path);
            }
        }

        return $request;
    }

    /**
     * @throws BusinessRuleViolation `deletion_request_not_pending`
     */
    public function reject(ProviderProfile $profile, string $requestUuid, string $reason, Admin $actor): AccountDeletionRequest
    {
        return $this->db->transaction(function () use ($profile, $requestUuid, $reason, $actor): AccountDeletionRequest {
            $locked = $this->profiles->lockForUpdate($profile->id) ?? throw (new ModelNotFoundException)->setModel(ProviderProfile::class);
            $user = $this->users->lockById($locked->user_id);

            if (! $user instanceof User) {
                throw (new ModelNotFoundException)->setModel(User::class);
            }

            $request = $this->pendingRequest($user, $requestUuid);
            $before = ['status' => $locked->status->value];

            $this->requests->update($request, [
                'status' => DeletionRequestStatus::Rejected,
                'processed_at' => now(),
                'processed_by_admin_id' => $actor->id,
                'processing_note' => $reason,
            ]);

            if ($user->status === UserStatus::DeletionPending) {
                $restored = $request->previous_user_status === UserStatus::DeletionPending ? UserStatus::Active : $request->previous_user_status;
                $this->users->setStatus($user, $restored);
            }

            $this->restoreProvider($user, $request, $actor);
            $this->audit->record(AuditAction::Rejected, $locked, $actor, $before, ['status' => $locked->refresh()->status->value], $reason, ['deletion_request' => $request->uuid]);

            return $request;
        });
    }

    /**
     * Deletes the personal data stored with the provider's documents and vehicles; returns the file paths to remove
     * from storage after the commit.
     *
     * @return list<string|null>
     */
    private function removeFiles(ProviderProfile $profile): array
    {
        $paths = $this->providerDocuments->withFilesForOwner($profile->id)->pluck('path')->all();
        $this->providerDocuments->scrubForOwner($profile->id);

        foreach ($this->vehicles->forProvider($profile) as $vehicle) {
            $paths = [...$paths, ...$this->vehicleDocuments->withFilesForOwner($vehicle->id)->pluck('path')->all()];
            $this->vehicleDocuments->scrubForOwner($vehicle->id);

            foreach ($this->photos->forVehicle($vehicle) as $photo) {
                $paths[] = $photo->path;
                $this->photos->delete($photo);
            }

            $this->vehicles->applyStatus($vehicle, VehicleStatus::Inactive, null, null);
        }

        return array_values($paths);
    }

    private function lockedProfile(User $user): ?ProviderProfile
    {
        $profile = $this->profiles->findByUserId($user->id);

        return $profile === null ? null : $this->profiles->lockForUpdate($profile->id);
    }

    private function pendingRequest(User $user, string $uuid): AccountDeletionRequest
    {
        $request = $this->requests->findForUser($user, $uuid) ?? throw (new ModelNotFoundException)->setModel(AccountDeletionRequest::class);

        if ($request->status !== DeletionRequestStatus::Pending) {
            throw BusinessRuleViolation::make('deletion_request_not_pending');
        }

        return $request;
    }
}
