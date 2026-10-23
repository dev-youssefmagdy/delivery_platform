<?php

namespace App\DataTables\Admin\Booking;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Booking\BookingAdminFilterData;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Auth\PhoneNumber;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;

/**
 * Bookings listing (Plan Appendix C filters; SRS §16.6): status and payment badges, masked customer and provider
 * contacts, the fare (agreed once a provider was chosen), and a "needs support" flag. The global search box is off:
 * the filters card carries code and phone searches. Pass a provider id through `forProvider()` for the Jobs tab.
 */
class BookingsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'bookings-table';

    protected bool $globalSearch = false;

    /** @var list<array{0: int, 1: string}> */
    protected array $defaultOrder = [[9, 'desc']];

    public function __construct(private readonly BookingRepositoryInterface $bookings)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return static::TABLE_ID;
    }

    /**
     * @return Builder<Booking>
     */
    public function query(): Builder
    {
        return $this->bookings->adminDatatableQuery($this->filters());
    }

    protected function filters(): BookingAdminFilterData
    {
        return BookingAdminFilterData::fromRequest($this->filterRequest());
    }

    protected function columns(): array
    {
        return [
            $this->column('code', __('admin.bookings.fields.code'), orderable: true, name: 'bookings.code'),
            $this->column('service', __('admin.bookings.fields.service')),
            $this->column('booking_type', __('admin.bookings.fields.booking_type'), orderable: true, name: 'bookings.booking_type'),
            $this->column('status', __('admin.bookings.fields.status'), orderable: true, name: 'bookings.status'),
            $this->column('customer', __('admin.bookings.fields.customer')),
            $this->column('provider', __('admin.bookings.fields.provider')),
            $this->column('city', __('admin.bookings.fields.city')),
            $this->column('fare', __('admin.bookings.fields.fare'), orderable: true, name: 'bookings.proposed_fare_minor'),
            $this->column('payment_status', __('admin.bookings.fields.payment_status'), orderable: true, name: 'bookings.payment_status'),
            $this->column('created_at', __('admin.bookings.fields.created'), orderable: true, name: 'bookings.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['code', 'service', 'booking_type', 'status', 'customer', 'provider', 'city', 'fare', 'payment_status', 'created_at', 'actions']);

        $table->editColumn('code', static function (Model $row): string {
            $html = '<a href="'.e(route('admin.bookings.show', $row)).'" class="font-medium text-brand-700 hover:underline ltr-only">'.e((string) $row->getAttribute('code')).'</a>';

            return $row instanceof Booking && $row->support_flagged_at !== null
                ? $html.' '.Blade::renderComponent(new Badge(label: __('admin.bookings.flags.support'), color: 'warning'))
                : $html;
        });
        $this->rawColumn('code');

        $table->addColumn('service', static function (Model $row): string {
            $name = $row instanceof Booking ? (array) ($row->service_snapshot['category']['name'] ?? []) : [];

            return (string) ($name[app()->getLocale()] ?? $name['ar'] ?? '—');
        });
        $table->editColumn('booking_type', static fn (Model $row): string => $row instanceof Booking ? e($row->booking_type->label()) : '');
        $this->enumBadgeColumn($table, 'status');
        $this->enumBadgeColumn($table, 'payment_status');

        $this->userColumn($table, 'customer', static fn (Booking $booking): array => [
            $booking->customer->name ?? __('admin.customers.unnamed'),
            $booking->customer->phone_e164 === null ? null : PhoneNumber::mask($booking->customer->phone_e164),
        ]);
        $this->userColumn($table, 'provider', static fn (Booking $booking): ?array => $booking->provider === null ? null : [
            $booking->provider->user->name ?? __('admin.providers.unnamed'),
            $booking->provider->user->phone_e164 === null ? null : PhoneNumber::mask($booking->provider->user->phone_e164),
        ]);
        $table->addColumn('city', static fn (Model $row): string => $row instanceof Booking ? (string) ($row->city->name ?? '—') : '');

        $table->addColumn('fare', static function (Model $row): string {
            if (! $row instanceof Booking) {
                return '';
            }

            $minor = $row->agreed_fare_minor ?? $row->proposed_fare_minor;

            return app(MoneyFormatter::class)->format(Money::of($minor, app(CurrencyRegistry::class)->get($row->currency)));
        });
        $this->dateColumn($table, 'created_at');

        $this->actionsColumn($table, static fn (Booking $booking): array => [TableAction::view(route('admin.bookings.show', $booking), $booking)]);
    }
}
