<?php

namespace App\Services\Finance;

use App\DTOs\Finance\CommissionContext;
use App\DTOs\Finance\CommissionTerms;
use App\Models\Booking;
use Carbon\CarbonInterface;

/**
 * The commission rule and version a booking is bound to (Plan I4, SRS §12.4). Resolved once, when the customer picks
 * the provider, and stored on the booking in the same transaction as the selection (OfferSelectionService): after
 * that, editing the rule changes nothing for this booking. Settlement reads the snapshot, never the live rule.
 */
final class CommissionSnapshotService
{
    public function __construct(private readonly CommissionResolver $resolver) {}

    public function termsFor(Booking $booking, CarbonInterface $at): CommissionTerms
    {
        return $this->resolver->resolve(new CommissionContext(
            serviceCategoryId: $booking->service_category_id,
            serviceZoneId: $booking->service_zone_id,
            currency: $booking->currency,
            at: $at,
        ));
    }

    /**
     * The booking columns to write at selection time.
     *
     * @return array<string, mixed>
     */
    public function attributesFor(Booking $booking, CarbonInterface $at): array
    {
        $terms = $this->termsFor($booking, $at);

        return [
            'commission_snapshot' => $terms->toSnapshot(),
            'commission_rule_id' => $terms->ruleId,
            'commission_rule_version_id' => $terms->versionId,
        ];
    }
}
