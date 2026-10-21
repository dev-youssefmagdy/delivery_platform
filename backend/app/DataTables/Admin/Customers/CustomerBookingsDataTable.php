<?php

namespace App\DataTables\Admin\Customers;

use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Bookings tab of the customer page: this customer's bookings, newest first, with status and payment badges. Free text
 * the customer wrote (addresses, details) is not shown here; the code links to the booking page (P2.8).
 */
final class CustomerBookingsDataTable extends CustomerTabDataTable
{
    public const TABLE_ID = 'customer-bookings-table';

    protected array $defaultOrder = [[7, 'desc']];

    public function __construct(private readonly BookingRepositoryInterface $bookings)
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
        return $this->bookings->datatableQueryForCustomer($this->customer());
    }

    protected function columns(): array
    {
        return [
            $this->column('code', __('admin.customers.bookings.code'), orderable: true),
            $this->column('service', __('admin.customers.bookings.service')),
            $this->column('booking_type', __('admin.customers.bookings.type'), orderable: true),
            $this->column('status', __('admin.customers.fields.status'), orderable: true),
            $this->column('proposed_fare_minor', __('admin.customers.bookings.fare'), orderable: true),
            $this->column('payment_method', __('admin.customers.bookings.payment_method')),
            $this->column('payment_status', __('admin.customers.bookings.payment_status')),
            $this->column('created_at', __('admin.customers.bookings.created'), orderable: true),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['code', 'service', 'booking_type', 'status', 'proposed_fare_minor', 'payment_method', 'payment_status', 'created_at']);
        $table->editColumn('code', static fn (Model $row): string => '<a href="'.e(route('admin.bookings.show', $row)).'" class="ltr-only font-medium text-brand-700 hover:underline">'.e((string) $row->getAttribute('code')).'</a>');
        $table->addColumn('service', static function (Model $row): string {
            $name = $row instanceof Booking ? (array) ($row->service_snapshot['category']['name'] ?? []) : [];

            return (string) ($name[app()->getLocale()] ?? $name['ar'] ?? '—');
        });
        $table->editColumn('booking_type', static fn (Model $row): string => $row instanceof Booking ? e($row->booking_type->label()) : '');
        $this->enumBadgeColumn($table, 'status');
        $this->enumBadgeColumn($table, 'payment_status');
        $table->editColumn('proposed_fare_minor', static fn (Model $row): string => $row instanceof Booking && $row->proposed_fare !== null ? e(app(\App\Support\Money\MoneyFormatter::class)->format($row->proposed_fare)) : '—');
        $table->editColumn('payment_method', static fn (Model $row): string => $row instanceof Booking ? e($row->payment_method->label()) : '');
        $this->dateColumn($table, 'created_at');
        $this->rawColumn('code');
    }
}
