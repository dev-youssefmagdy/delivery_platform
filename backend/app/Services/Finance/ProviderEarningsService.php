<?php

namespace App\Services\Finance;

use App\Enums\Payments\PaymentMethodType;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Finance\FinancialPositionRepositoryInterface;
use App\Repositories\Contracts\Finance\ProviderEarningsRepositoryInterface;
use App\Services\Payout\ProviderBalanceService;
use App\Services\Settings\SettingsService;
use App\Enums\Settings\SettingKey;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Earnings of a provider from the stored price breakdowns of their settled bookings (SRS §7.6): an overview by day / week /
 * month and by service category, and the booking-level breakdown. The numbers are the ones the ledger was posted from
 * (`bookings.price_breakdown`); a provider-funded coupon is already inside `provider_net` and also shown on its own line.
 */
final class ProviderEarningsService
{
    public const PERIODS = ['day', 'week', 'month'];

    public function __construct(
        private readonly ProviderEarningsRepositoryInterface $earnings,
        private readonly ProviderBalanceService $balances,
        private readonly FinancialPositionRepositoryInterface $position,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(ProviderProfile $provider, string $period, ?CarbonInterface $anchor = null): array
    {
        $zone = (string) config('admin.display_timezone', 'UTC');
        $start = CarbonImmutable::instance($anchor ?? now())->setTimezone($zone);
        $start = match ($period) {
            'day' => $start->startOfDay(),
            'week' => $start->startOfWeek(CarbonInterface::SATURDAY),
            default => $start->startOfMonth(),
        };
        $end = match ($period) {
            'day' => $start->addDay(),
            'week' => $start->addWeek(),
            default => $start->addMonth(),
        };

        $balance = $this->balances->forProvider($provider);
        $bookings = $this->earnings->settledBetween($provider->id, $start, $end, $balance->currency);

        $totals = ['jobs' => 0, 'gross_minor' => 0, 'commission_minor' => 0, 'discount_provider_minor' => 0, 'discount_platform_minor' => 0, 'tax_minor' => 0, 'tips_minor' => 0, 'net_minor' => 0, 'earnings_minor' => 0, 'cash_collected_minor' => 0];
        $categories = [];
        $series = [];

        foreach ($bookings as $booking) {
            $b = (array) $booking->price_breakdown;
            $earning = (int) ($b['provider_earning_minor'] ?? 0);
            $day = CarbonImmutable::instance($booking->completed_at ?? $booking->settled_at ?? now())->setTimezone($zone)->toDateString();

            $totals['jobs']++;
            $totals['gross_minor'] += (int) ($b['provider_gross_minor'] ?? 0);
            $totals['commission_minor'] += (int) ($b['commission_minor'] ?? 0) + (int) ($b['tip_commission_minor'] ?? 0);
            $totals['discount_provider_minor'] += (int) ($b['discount_provider_minor'] ?? 0);
            $totals['discount_platform_minor'] += (int) ($b['discount_platform_minor'] ?? 0);
            $totals['tax_minor'] += (int) ($b['tax_minor'] ?? 0);
            $totals['tips_minor'] += (int) ($b['tip_minor'] ?? 0);
            $totals['net_minor'] += (int) ($b['provider_net_minor'] ?? 0);
            $totals['earnings_minor'] += $earning;
            $totals['cash_collected_minor'] += $booking->payment_method === PaymentMethodType::Cash ? (int) ($b['customer_payable_minor'] ?? 0) : 0;

            $category = (array) ($booking->service_snapshot['category'] ?? []);
            $key = (int) $booking->service_category_id;
            $categories[$key] ??= ['service_category_id' => $key, 'code' => $category['code'] ?? null, 'name' => (array) ($category['name'] ?? []), 'jobs' => 0, 'gross_minor' => 0, 'earnings_minor' => 0];
            $categories[$key]['jobs']++;
            $categories[$key]['gross_minor'] += (int) ($b['provider_gross_minor'] ?? 0);
            $categories[$key]['earnings_minor'] += $earning;

            $series[$day] ??= ['date' => $day, 'jobs' => 0, 'earnings_minor' => 0];
            $series[$day]['jobs']++;
            $series[$day]['earnings_minor'] += $earning;
        }

        ksort($series);

        return [
            'period' => $period,
            'from' => $start->utc()->toIso8601ZuluString(),
            'to' => $end->utc()->toIso8601ZuluString(),
            'currency' => $balance->currency,
            'totals' => $totals,
            'by_category' => array_values($categories),
            'series' => array_values($series),
            'wallet' => [
                'balance_minor' => $balance->walletMinor,
                'reserved_minor' => $balance->reservedMinor,
                'available_minor' => $balance->availableMinor,
                'cash_debt_minor' => $this->position->cashDebtMinor($provider),
                'cash_debt_limit_minor' => $this->settings->int(SettingKey::ProviderCashDebtLimitMinor),
                'min_payout_minor' => $balance->minMinor,
                'max_payout_minor' => $balance->maxMinor,
            ],
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Booking>
     */
    public function transactions(ProviderProfile $provider, ?CarbonInterface $from, ?CarbonInterface $to, int $perPage): LengthAwarePaginator
    {
        return $this->earnings->transactionsPage($provider->id, $from, $to, max(1, min(50, $perPage)));
    }
}
