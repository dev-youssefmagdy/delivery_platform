<?php

namespace App\Services\Booking;

use App\Enums\Settings\SettingKey;
use App\Repositories\Contracts\Booking\RatingRepositoryInterface;
use App\Services\Settings\SettingsService;

/**
 * Public rating of a provider or customer (SRS §15). Formula (versioned in ADR-99): the arithmetic mean of the stars of
 * the ratee's `visible` ratings, rounded half-up to two decimals, stored with the count. Hidden and under-review ratings
 * do not count. The score is "provisional" until `rating_min_sample` ratings exist: apps show it as such and no
 * automatic action is ever taken from a low rating alone.
 */
final class RatingAggregationService
{
    public function __construct(
        private readonly RatingRepositoryInterface $ratings,
        private readonly SettingsService $settings,
    ) {}

    /**
     * Recomputes and stores the aggregate from the rows (never incrementally, so moderation and corrections stay exact).
     *
     * @return array{average: string, count: int}
     */
    public function recompute(string $rateeType, int $rateeId): array
    {
        $totals = $this->ratings->visibleTotals($rateeType, $rateeId);
        $average = self::average($totals['sum'], $totals['count']);
        $this->ratings->storeAggregate($rateeType, $rateeId, $average, $totals['count']);

        return ['average' => $average, 'count' => $totals['count']];
    }

    public static function average(int $starSum, int $count): string
    {
        if ($count === 0) {
            return '0.00';
        }

        $hundredths = intdiv($starSum * 100 * 2 + $count, $count * 2);

        return sprintf('%d.%02d', intdiv($hundredths, 100), $hundredths % 100);
    }

    public function isProvisional(int $count): bool
    {
        return $count < $this->settings->int(SettingKey::RatingMinSample);
    }
}
