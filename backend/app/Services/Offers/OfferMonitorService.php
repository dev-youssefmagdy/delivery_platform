<?php

namespace App\Services\Offers;

use App\DTOs\Booking\OfferFilterData;
use App\DTOs\Operations\OfferStats;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;

/**
 * Numbers of the Offers monitor (SRS §16.7): conversion from submitted to selected offers and the number flagged as
 * anomalies, for the filters currently applied. Read-only: accepted offers are never edited from here.
 */
final class OfferMonitorService
{
    public function __construct(private readonly OfferRepositoryInterface $offers) {}

    public function stats(OfferFilterData $filters): OfferStats
    {
        return $this->offers->statsFor($filters);
    }
}
