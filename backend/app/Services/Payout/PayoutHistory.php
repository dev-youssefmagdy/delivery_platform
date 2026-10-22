<?php

namespace App\Services\Payout;

use App\Enums\Notification\NotificationEvent;
use App\Enums\Payout\PayoutStatus;
use App\Models\Admin;
use App\Models\PayoutRequest;
use App\Repositories\Contracts\Payout\PayoutEventRepositoryInterface;
use App\Services\Notification\PartyNotifier;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Writes the append-only timeline of a payout (one row per status change, with who did it) and tells the provider about
 * the steps that matter (SRS §14.1: requested, approved, rejected, paid, failed). Every payout status change passes here.
 */
final class PayoutHistory
{
    public function __construct(
        private readonly PayoutEventRepositoryInterface $events,
        private readonly PartyNotifier $notifier,
    ) {}

    /**
     * @param array<string, mixed> $meta
     */
    public function record(PayoutRequest $payout, ?PayoutStatus $from, PayoutStatus $to, ?Authenticatable $actor, ?string $note = null, array $meta = []): void
    {
        $this->events->insert([
            'payout_request_id' => $payout->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_type' => $actor instanceof Admin ? 'admin' : ($actor === null ? 'system' : 'provider'),
            'actor_id' => $actor?->getAuthIdentifier(),
            'note' => $note,
            'meta' => $meta === [] ? null : $meta,
        ]);

        $event = match ($to) {
            PayoutStatus::Requested => NotificationEvent::PayoutRequested,
            PayoutStatus::Approved => NotificationEvent::PayoutApproved,
            PayoutStatus::Rejected => NotificationEvent::PayoutRejected,
            PayoutStatus::Paid => NotificationEvent::PayoutPaid,
            PayoutStatus::Failed, PayoutStatus::Reversed => NotificationEvent::PayoutFailed,
            default => null,
        };

        if ($event !== null) {
            $payout->loadMissing('provider');
            $this->notifier->providerAccount($payout->provider, $event, $payout->uuid.':'.$to->value, linkId: $payout->uuid);
        }
    }
}
