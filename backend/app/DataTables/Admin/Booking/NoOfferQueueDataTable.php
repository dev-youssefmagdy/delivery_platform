<?php

namespace App\DataTables\Admin\Booking;

use App\DataTables\Admin\AdminDataTable;
use App\Enums\Booking\BookingOverrideAction;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Admin\Tables\TableActionKind;
use App\Support\Auth\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * The no-offer queue (SRS §16.6): bookings still searching without an active offer for longer than
 * `booking.operations.no_offer_after_seconds`, plus bookings that expired without a provider within the last
 * `expired_window_hours`. Searching ones can be retried, extended or cancelled; every row can be contacted. The action
 * forms open in the global modal (the same fragments the booking page uses).
 */
final class NoOfferQueueDataTable extends AdminDataTable
{
    public const TABLE_ID = 'no-offer-queue-table';

    protected bool $globalSearch = false;

    /** @var list<array{0: int, 1: string}> */
    protected array $defaultOrder = [[5, 'asc']];

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
        $now = CarbonImmutable::now();

        return $this->bookings->noOfferQueueQuery(
            $now->subSeconds((int) config('booking.operations.no_offer_after_seconds', 120)),
            $now->subHours((int) config('booking.operations.expired_window_hours', 24)),
        );
    }

    protected function columns(): array
    {
        return [
            $this->column('code', __('admin.bookings.fields.code'), orderable: true, name: 'bookings.code'),
            $this->column('service', __('admin.bookings.fields.service')),
            $this->column('status', __('admin.bookings.fields.status'), orderable: true, name: 'bookings.status'),
            $this->column('customer', __('admin.bookings.fields.customer')),
            $this->column('city', __('admin.bookings.fields.city')),
            $this->column('created_at', __('admin.bookings.fields.created'), orderable: true, name: 'bookings.created_at'),
            $this->column('waiting', __('admin.bookings.queue.waiting')),
            $this->column('dispatch', __('admin.bookings.queue.dispatch')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['code', 'service', 'status', 'customer', 'city', 'created_at', 'waiting', 'dispatch', 'actions']);

        $table->editColumn('code', static fn (Model $row): string => '<a href="'.e(route('admin.bookings.show', $row)).'" class="font-medium text-brand-700 hover:underline ltr-only">'.e((string) $row->getAttribute('code')).'</a>');
        $this->rawColumn('code');
        $table->addColumn('service', static function (Model $row): string {
            $name = $row instanceof Booking ? (array) ($row->service_snapshot['category']['name'] ?? []) : [];

            return (string) ($name[app()->getLocale()] ?? $name['ar'] ?? '—');
        });
        $this->enumBadgeColumn($table, 'status');
        $this->userColumn($table, 'customer', static fn (Booking $booking): ?array => [
            $booking->customer->name ?? __('admin.customers.unnamed'),
            $booking->customer->phone_e164 === null ? null : PhoneNumber::mask($booking->customer->phone_e164),
        ]);
        $table->addColumn('city', static fn (Model $row): string => $row instanceof Booking ? (string) ($row->city->name ?? '—') : '');
        $this->dateColumn($table, 'created_at');
        $table->addColumn('waiting', static function (Model $row): string {
            if (! $row instanceof Booking) {
                return '';
            }

            $end = $row->status->isSearching() ? CarbonImmutable::now() : CarbonImmutable::instance($row->updated_at);

            return CarbonImmutable::instance($row->created_at)->locale(app()->getLocale())->diffForHumans($end, ['syntax' => CarbonImmutable::DIFF_ABSOLUTE, 'parts' => 2, 'short' => true]);
        });
        $table->addColumn('dispatch', static fn (Model $row): string => __('admin.bookings.queue.dispatch_value', [
            'recipients' => (int) $row->getAttribute('recipients_count'),
            'offers' => (int) $row->getAttribute('offers_count'),
        ]));

        $this->actionsColumn($table, function (Booking $booking): array {
            $form = static fn (string $name): string => route('admin.bookings.override-form', ['booking' => $booking, 'form' => $name, 'context' => 'queue']);
            $modal = static fn (string $label, string $url, string $icon, string $ability, string $variant = 'neutral'): TableAction => new TableAction($label, $url, TableActionKind::Modal, $icon, $variant, $ability, $booking);

            $actions = [TableAction::view(route('admin.bookings.show', $booking), $booking)];

            if ($booking->status->isSearching()) {
                $actions[] = $modal(__('admin.bookings.actions.redispatch'), $form(BookingOverrideAction::RedispatchNow->value), 'refresh', 'manage');
                $actions[] = $modal(__('admin.bookings.actions.extend_search'), $form(BookingOverrideAction::ExtendSearch->value), 'clock', 'manage');
            }

            $actions[] = $modal(__('admin.bookings.actions.contact'), $form('contact'), 'inbox', 'manage');

            if ($booking->status->isSearching()) {
                $actions[] = $modal(__('admin.bookings.actions.cancel'), $form(BookingOverrideAction::CancelByOperations->value), 'x-mark', 'cancelByOperations', 'danger');
            }

            return $actions;
        });
    }
}
