<?php

namespace App\Services\Provider;

use App\Enums\Notification\NotificationEvent;
use App\Enums\Provider\ProviderStatus;
use App\Models\OwnedDocument;
use App\Models\ProviderProfile;
use App\Services\Notification\PartyNotifier;

/**
 * Notification hook of the provider module (SRS §14.1): application and standing changes, and document expiry. The
 * texts never include the review reason (it is shown inside the app, behind the login), only that something changed.
 */
class ProviderNotifier
{
    public function __construct(private readonly PartyNotifier $parties) {}

    public function statusChanged(ProviderProfile $profile, ?ProviderStatus $from, ProviderStatus $to, ?string $reason): void
    {
        $event = match (true) {
            $to === ProviderStatus::UnderReview => NotificationEvent::ProviderApplicationSubmitted,
            $to === ProviderStatus::MoreInformationRequired => NotificationEvent::ProviderApplicationMoreInfo,
            $to === ProviderStatus::Approved && in_array($from, [ProviderStatus::TemporarilySuspended, ProviderStatus::PermanentlySuspended], true) => NotificationEvent::ProviderReactivated,
            $to === ProviderStatus::Approved => NotificationEvent::ProviderApplicationApproved,
            $to === ProviderStatus::Rejected => NotificationEvent::ProviderApplicationRejected,
            in_array($to, [ProviderStatus::TemporarilySuspended, ProviderStatus::PermanentlySuspended], true) => NotificationEvent::ProviderSuspended,
            default => null,
        };

        if ($event === null) {
            return;
        }

        $this->parties->providerAccount($profile, $event, $to->value.':'.($profile->status_changed_at?->getTimestamp() ?? 0));
    }

    public function documentExpiring(ProviderProfile $profile, OwnedDocument $document, int $daysLeft): void
    {
        $this->parties->providerAccount(
            $profile,
            NotificationEvent::DocumentExpiring,
            $document->uuid.':'.($document->expiry_reminder_days ?? $daysLeft),
            ['days' => $daysLeft, 'document' => fn (string $locale): string => $this->documentName($document, $locale)],
            $document->uuid,
        );
    }

    public function documentExpired(ProviderProfile $profile, OwnedDocument $document): void
    {
        $this->parties->providerAccount(
            $profile,
            NotificationEvent::DocumentExpired,
            $document->uuid,
            ['document' => fn (string $locale): string => $this->documentName($document, $locale)],
            $document->uuid,
        );
    }

    private function documentName(OwnedDocument $document, string $locale): string
    {
        $document->loadMissing('documentType');

        return (string) $document->documentType->getTranslation('name', $locale);
    }
}
