<?php

namespace App\Repositories\Eloquent\Catalog;

use App\Models\ServiceCategory;
use App\Repositories\Contracts\Catalog\CatalogUsageRepositoryInterface;
use Illuminate\Support\Facades\Schema;
use LogicException;

/**
 * There is no bookings table before P2.3, so no booking can be active and the honest count is 0. As soon as
 * the table exists this implementation MUST be replaced with the real query; until then it fails loudly instead
 * of silently under-counting (the guard would otherwise let admins break live bookings).
 */
final class CatalogUsageRepository implements CatalogUsageRepositoryInterface
{
    public function activeBookingCount(ServiceCategory $category): int
    {
        if (Schema::hasTable('bookings')) {
            throw new LogicException('CatalogUsageRepository::activeBookingCount() must query bookings now that the table exists (P2.3).');
        }

        return 0;
    }
}
