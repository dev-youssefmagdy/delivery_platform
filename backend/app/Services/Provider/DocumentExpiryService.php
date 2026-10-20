<?php

namespace App\Services\Provider;

use App\Enums\Common\AuditAction;
use App\Enums\Provider\DocumentStatus;
use App\Models\OwnedDocument;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Provider\DocumentRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderDocumentRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleDocumentRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;

/**
 * Daily job `documents:check-expiry`: marks approved documents whose expiry date passed as `expired` (audited as the
 * system), sends expiry reminders at the configured thresholds, and takes providers who are no longer eligible
 * offline. Eligibility itself is computed live, so nothing else needs recomputing. Idempotent.
 */
final class DocumentExpiryService
{
    public function __construct(
        private readonly ProviderDocumentRepositoryInterface $providerDocuments,
        private readonly VehicleDocumentRepositoryInterface $vehicleDocuments,
        private readonly ProviderProfileRepositoryInterface $profiles,
        private readonly ProviderEligibilityService $eligibility,
        private readonly ProviderNotifier $notifier,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return array{expired: int, reminded: int}
     */
    public function run(int $limit = 500): array
    {
        $today = CarbonImmutable::today();
        $expired = 0;
        $reminded = 0;
        /** @var array<int, ProviderProfile> $touched */
        $touched = [];

        foreach ([$this->providerDocuments, $this->vehicleDocuments] as $repository) {
            foreach ($repository->expiredBefore($today, $limit) as $document) {
                $profile = $this->expire($repository, $document);

                if ($profile !== null) {
                    $expired++;
                    $touched[$profile->id] = $profile;
                }
            }

            $reminded += $this->remind($repository, $today, $limit);
        }

        foreach ($touched as $profile) {
            $this->takeOfflineIfIneligible($profile);
        }

        return ['expired' => $expired, 'reminded' => $reminded];
    }

    private function expire(DocumentRepositoryInterface $repository, OwnedDocument $candidate): ?ProviderProfile
    {
        return $this->db->transaction(function () use ($repository, $candidate): ?ProviderProfile {
            $document = $repository->lockForUpdate($candidate->id);

            if ($document === null || $document->status !== DocumentStatus::Approved || $document->expires_at === null || $document->expires_at->greaterThanOrEqualTo(CarbonImmutable::today())) {
                return null;
            }

            $repository->applyReview($document, DocumentStatus::Expired, null, null);
            $profile = $this->ownerProfile($document);
            $this->audit->record(
                AuditAction::StatusChanged,
                $document->auditTarget(),
                null,
                ['status' => DocumentStatus::Approved->value],
                ['status' => DocumentStatus::Expired->value],
                (string) __('admin.providers.document_expired_reason'),
                ['document' => $document->uuid, 'document_type' => $document->documentType->code],
            );
            $this->notifier->documentExpired($profile, $document);

            return $profile;
        });
    }

    private function remind(DocumentRepositoryInterface $repository, CarbonImmutable $today, int $limit): int
    {
        /** @var list<int> $thresholds */
        $thresholds = array_map('intval', (array) config('provider.documents.expiry_reminder_days', [30, 7]));
        rsort($thresholds);

        if ($thresholds === []) {
            return 0;
        }

        $reminded = 0;

        foreach ($repository->expiringBetween($today, $today->addDays($thresholds[0]), $limit) as $document) {
            $daysLeft = (int) $today->diffInDays($document->expires_at, false);
            $due = array_values(array_filter($thresholds, static fn (int $threshold): bool => $daysLeft <= $threshold));
            $threshold = $due === [] ? null : min($due);

            if ($threshold === null || ($document->expiry_reminder_days !== null && $document->expiry_reminder_days <= $threshold)) {
                continue;
            }

            $repository->recordReminder($document, $threshold);
            $this->notifier->documentExpiring($this->ownerProfile($document), $document, $daysLeft);
            $reminded++;
        }

        return $reminded;
    }

    private function takeOfflineIfIneligible(ProviderProfile $profile): void
    {
        $fresh = $this->profiles->find($profile->id);

        if ($fresh instanceof ProviderProfile && $fresh->is_online && ! $this->eligibility->check($fresh)->isEligible()) {
            $this->profiles->setOnline($fresh, false);
        }
    }

    private function ownerProfile(OwnedDocument $document): ProviderProfile
    {
        return $document->ownerProfile();
    }
}
