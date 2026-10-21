<?php

namespace App\Repositories\Eloquent\Catalog;

use App\Models\ServiceCategory;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Catalog\CatalogUsageRepositoryInterface;

/**
 * Usage counts of catalog entries by bookings (ADR-66): every booking that is still in flight counts, scheduled ones
 * included, so a deactivation guard can never under-count.
 */
final class CatalogUsageRepository implements CatalogUsageRepositoryInterface
{
    public function __construct(private readonly BookingRepositoryInterface $bookings) {}

    public function activeBookingCount(ServiceCategory $category): int
    {
        return $this->bookings->inFlightCountForCategory($category->id);
    }
}
