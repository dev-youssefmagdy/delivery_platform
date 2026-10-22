<?php

namespace App\DataTables\Admin\Provider;

use App\DataTables\Admin\Finance\Concerns\FormatsLedgerMoney;
use App\Enums\Payments\PaymentMethodType;
use App\Models\Booking;
use App\Repositories\Contracts\Finance\ProviderEarningsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Booking-level earnings of a provider (Earnings tab): every figure comes from the stored price breakdown the ledger was posted
 * from, so the rows add up to the wallet. A provider-funded coupon is shown on its own column and is already inside the net.
 */
final class ProviderEarningsDataTable extends ProviderTabDataTable
{
    use FormatsLedgerMoney;

    public const TABLE_ID = 'provider-earnings-table';

    protected array $defaultOrder = [[0, 'desc']];

    public function __construct(private readonly ProviderEarningsRepositoryInterface $earnings)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<Booking>
     */
    public function query(): Builder
    {
        return $this->earnings->transactionsQuery($this->provider()->id);
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'bookings.id'),
            $this->column('code', __('admin.providers.earnings.columns.booking')),
            $this->column('completed_at', __('admin.providers.earnings.columns.completed'), orderable: true, name: 'bookings.completed_at'),
            $this->column('gross', __('admin.providers.earnings.columns.gross')),
            $this->column('commission', __('admin.providers.earnings.columns.commission')),
            $this->column('discount_provider', __('admin.providers.earnings.columns.provider_discount')),
            $this->column('tip', __('admin.providers.earnings.columns.tip')),
            $this->column('net', __('admin.providers.earnings.columns.net')),
            $this->column('earning', __('admin.providers.earnings.columns.earning')),
            $this->column('cash', __('admin.providers.earnings.columns.cash_collected')),
            $this->column('payment_status', __('admin.providers.earnings.columns.payment_status')),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'code', 'completed_at', 'gross', 'commission', 'discount_provider', 'tip', 'net', 'earning', 'cash', 'payment_status']);
        $figure = fn (Booking $booking, string $key): string => $this->formatMinor((int) (((array) $booking->price_breakdown)[$key] ?? 0), $booking->currency);

        $table->editColumn('code', static fn (Booking $booking): string => '<span class="ltr-only">'.e($booking->code).'</span>');
        $this->rawColumn('code');
        $this->dateColumn($table, 'completed_at');
        $table->addColumn('gross', static fn (Booking $booking): string => $figure($booking, 'provider_gross_minor'));
        $table->addColumn('commission', fn (Booking $booking): string => $this->formatMinor((int) (((array) $booking->price_breakdown)['commission_minor'] ?? 0) + (int) (((array) $booking->price_breakdown)['tip_commission_minor'] ?? 0), $booking->currency));
        $table->addColumn('discount_provider', static fn (Booking $booking): string => $figure($booking, 'discount_provider_minor'));
        $table->addColumn('tip', static fn (Booking $booking): string => $figure($booking, 'tip_minor'));
        $table->addColumn('net', static fn (Booking $booking): string => $figure($booking, 'provider_net_minor'));
        $table->addColumn('earning', static fn (Booking $booking): string => $figure($booking, 'provider_earning_minor'));
        $table->addColumn('cash', static fn (Booking $booking): string => $booking->payment_method === PaymentMethodType::Cash ? $figure($booking, 'customer_payable_minor') : '—');
        $table->editColumn('payment_status', static fn (Booking $booking): string => $booking->payment_status->label());
    }
}
