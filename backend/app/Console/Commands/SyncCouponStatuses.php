<?php

namespace App\Console\Commands;

use App\Services\Coupon\CouponStatusSyncService;
use Illuminate\Console\Command;

/**
 * `coupons:sync-status` (every minute): scheduled -> active -> expired / exhausted, and releases reservations of bookings that
 * ended without a trip. Idempotent and safe to run concurrently (each change takes the coupon row lock).
 */
class SyncCouponStatuses extends Command
{
    protected $signature = 'coupons:sync-status';

    protected $description = 'Apply the scheduled coupon status transitions and release orphaned coupon reservations';

    public function handle(CouponStatusSyncService $sync): int
    {
        $counts = $sync->sync();

        $this->info(sprintf('Activated: %d, expired: %d, exhausted: %d, reopened: %d, reservations released: %d', $counts['activated'], $counts['expired'], $counts['exhausted'], $counts['reopened'], $counts['released']));

        return self::SUCCESS;
    }
}
