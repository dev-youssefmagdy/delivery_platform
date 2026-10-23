<?php

namespace App\DataTables\Admin\Provider;

use App\DTOs\Booking\BookingAdminFilterData;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Auth\PhoneNumber;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Jobs tab of the provider page: the bookings this provider holds or held to their end, newest first (SRS §16.3).
 * Customer contacts are masked; the booking page has the rest.
 */
final class ProviderJobsDataTable extends ProviderTabDataTable
{
    public const TABLE_ID = 'provider-jobs-table';

    /** @var list<array{0: int, 1: string}> */
    protected array $defaultOrder = [[6, 'desc']];

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
        return $this->bookings->adminDatatableQuery(BookingAdminFilterData::forProvider($this->provider()->id));
    }

    protected function columns(): array
    {
        return [
            $this->column('code', __('admin.bookings.fields.code'), orderable: true, name: 'bookings.code'),
            $this->column('service', __('admin.bookings.fields.service')),
            $this->column('status', __('admin.bookings.fields.status'), orderable: true, name: 'bookings.status'),
            $this->column('customer', __('admin.bookings.fields.customer')),
            $this->column('fare', __('admin.bookings.fields.fare')),
            $this->column('payment_status', __('admin.bookings.fields.payment_status')),
            $this->column('created_at', __('admin.bookings.fields.created'), orderable: true, name: 'bookings.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['code', 'service', 'status', 'customer', 'fare', 'payment_status', 'created_at', 'actions']);

        $table->editColumn('code', static fn (Model $row): string => '<a href="'.e(route('admin.bookings.show', $row)).'" class="font-medium text-brand-700 hover:underline ltr-only">'.e((string) $row->getAttribute('code')).'</a>');
        $this->rawColumn('code');
        $table->addColumn('service', static function (Model $row): string {
            $name = $row instanceof Booking ? (array) ($row->service_snapshot['category']['name'] ?? []) : [];

            return (string) ($name[app()->getLocale()] ?? $name['ar'] ?? '—');
        });
        $this->enumBadgeColumn($table, 'status');
        $this->enumBadgeColumn($table, 'payment_status');
        $this->userColumn($table, 'customer', static fn (Booking $booking): array => [
            $booking->customer->name ?? __('admin.customers.unnamed'),
            $booking->customer->phone_e164 === null ? null : PhoneNumber::mask($booking->customer->phone_e164),
        ]);
        $table->addColumn('fare', static function (Model $row): string {
            if (! $row instanceof Booking) {
                return '';
            }

            return app(MoneyFormatter::class)->format(Money::of($row->agreed_fare_minor ?? $row->proposed_fare_minor, app(CurrencyRegistry::class)->get($row->currency)));
        });
        $this->dateColumn($table, 'created_at');
        $this->actionsColumn($table, static fn (Booking $booking): array => [TableAction::view(route('admin.bookings.show', $booking), $booking)]);
    }
}
