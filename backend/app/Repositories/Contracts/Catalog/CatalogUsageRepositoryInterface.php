<?php

namespace App\Repositories\Contracts\Catalog;

use App\Models\ServiceCategory;

/**
 * Read-only usage counts of catalog entries by other modules (deactivation guards, Plan P1.4 step 3).
 */
interface CatalogUsageRepositoryInterface
{
    /**
     * Bookings in a non-terminal status that use the category.
     */
    public function activeBookingCount(ServiceCategory $category): int;
}
