<?php

namespace App\Repositories\Eloquent\Finance;

use App\Models\Booking;
use App\Repositories\Contracts\Finance\ProviderEarningsRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ProviderEarningsRepository implements ProviderEarningsRepositoryInterface
{
    private const COLUMNS = ['id', 'uuid', 'code', 'provider_id', 'service_category_id', 'service_snapshot', 'currency', 'payment_method', 'payment_status', 'completed_at', 'settled_at', 'price_breakdown'];

    public function settledBetween(int $providerId, CarbonInterface $from, CarbonInterface $to, ?string $currency = null): Collection
    {
        return $this->settled($providerId)
            ->where('completed_at', '>=', $from)
            ->where('completed_at', '<', $to)
            ->when($currency !== null, static fn (Builder $query) => $query->where('currency', $currency))
            ->orderBy('completed_at')
            ->orderBy('id')
            ->get(self::COLUMNS);
    }

    public function transactionsPage(int $providerId, ?CarbonInterface $from, ?CarbonInterface $to, int $perPage): LengthAwarePaginator
    {
        return $this->settled($providerId)
            ->when($from !== null, static fn (Builder $query) => $query->where('completed_at', '>=', $from))
            ->when($to !== null, static fn (Builder $query) => $query->where('completed_at', '<', $to))
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate($perPage, self::COLUMNS);
    }

    /**
     * @return Builder<Booking>
     */
    private function settled(int $providerId): Builder
    {
        return Booking::query()->where('provider_id', $providerId)->whereNotNull('settled_at')->whereNotNull('price_breakdown');
    }
}
