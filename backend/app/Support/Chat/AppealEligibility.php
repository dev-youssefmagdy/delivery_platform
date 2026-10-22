<?php

namespace App\Support\Chat;

use App\Enums\Identity\ClientApp;
use App\Models\Booking;

/**
 * Whether a cancelled booking carries a fee or a penalty the given side may dispute (P3.5 fee decision snapshot on the
 * booking). The customer appeals a fee charged to them; the driver a fee charged to them or a cancellation penalty.
 */
final class AppealEligibility
{
    public static function allowed(Booking $booking, ClientApp $app): bool
    {
        if (! $booking->status->isCancellation()) {
            return false;
        }

        $decision = (array) ($booking->cancellation_decision ?? []);
        $chargedToMe = (int) ($booking->cancellation_fee_minor ?? 0) > 0 && ($decision['payer'] ?? null) === $app->value;
        $penalised = $app === ClientApp::Provider && ! empty($decision['penalty']);

        return $chargedToMe || $penalised;
    }
}
