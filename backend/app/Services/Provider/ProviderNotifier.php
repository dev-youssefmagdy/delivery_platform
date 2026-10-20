<?php

namespace App\Services\Provider;

use App\Enums\Provider\ProviderStatus;
use App\Models\OwnedDocument;
use App\Models\ProviderProfile;
use Illuminate\Support\Facades\Log;

/**
 * Notification hook of the provider module. Until the notification platform (P3.6) it writes to the log channel only:
 * nothing is sent to the provider, and the log lines say so. P3.6 replaces the bodies with real notifications.
 */
class ProviderNotifier
{
    public function statusChanged(ProviderProfile $profile, ?ProviderStatus $from, ProviderStatus $to, ?string $reason): void
    {
        Log::info('provider.status_changed (notification stub, nothing sent)', [
            'provider' => $profile->uuid,
            'from' => $from?->value,
            'to' => $to->value,
            'has_reason' => $reason !== null,
        ]);
    }

    public function documentExpiring(ProviderProfile $profile, OwnedDocument $document, int $daysLeft): void
    {
        Log::info('provider.document_expiring (notification stub, nothing sent)', [
            'provider' => $profile->uuid,
            'document' => $document->uuid,
            'days_left' => $daysLeft,
        ]);
    }

    public function documentExpired(ProviderProfile $profile, OwnedDocument $document): void
    {
        Log::info('provider.document_expired (notification stub, nothing sent)', [
            'provider' => $profile->uuid,
            'document' => $document->uuid,
        ]);
    }
}
