<?php

namespace App\DataTables\Admin\Booking;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Booking\BookingAdjustmentFilterData;
use App\Enums\Common\ActorType;
use App\Models\BookingAdjustmentRequest;
use App\Repositories\Contracts\Booking\BookingAdjustmentRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Booking fare adjustments (extra stop, waiting, toll / parking, scope change): the listing page and, with `forBooking`,
 * the Adjustments tab of the booking page (the `{booking}` route parameter pins it).
 */
final class BookingAdjustmentsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'booking-adjustments-table';

    protected bool $globalSearch = false;

    public function __construct(private readonly BookingAdjustmentRepositoryInterface $adjustments)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<BookingAdjustmentRequest>
     */
    public function query(): Builder
    {
        $filters = BookingAdjustmentFilterData::fromRequest($this->filterRequest());
        $booking = $this->request()->route('booking');

        return $this->adjustments->datatableQuery($booking instanceof \App\Models\Booking ? $filters->forBooking($booking->id) : $filters);
    }

    protected function columns(): array
    {
        return [
            $this->column('request', __('admin.booking_adjustments.fields.request')),
            $this->column('booking', __('admin.booking_adjustments.fields.booking')),
            $this->column('type', __('admin.booking_adjustments.fields.type'), orderable: true, name: 'booking_adjustment_requests.type'),
            $this->column('requester', __('admin.booking_adjustments.fields.requester'), orderable: true, name: 'booking_adjustment_requests.requester_type'),
            $this->column('amount', __('admin.booking_adjustments.fields.amount'), orderable: true, name: 'booking_adjustment_requests.amount_minor'),
            $this->column('new_total', __('admin.booking_adjustments.fields.new_total')),
            $this->column('status', __('admin.booking_adjustments.fields.status'), orderable: true, name: 'booking_adjustment_requests.status'),
            $this->column('created_at', __('admin.booking_adjustments.fields.created'), orderable: true, name: 'booking_adjustment_requests.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['request', 'booking', 'type', 'requester', 'amount', 'new_total', 'status', 'created_at', 'actions']);

        $table->addColumn('request', static fn (BookingAdjustmentRequest $row): string => '<a href="'.e(route('admin.booking-adjustments.show', $row->uuid)).'" class="ltr-only font-medium text-brand-700 hover:underline">'.e(Str::substr($row->uuid, 0, 8)).'</a>');
        $this->rawColumn('request');
        $table->addColumn('booking', static fn (BookingAdjustmentRequest $row): string => '<a href="'.e(route('admin.bookings.show', $row->booking->uuid)).'" class="ltr-only text-brand-700 hover:underline">'.e($row->booking->code).'</a>');
        $this->rawColumn('booking');
        $table->editColumn('type', static fn (BookingAdjustmentRequest $row): string => e($row->type->label()));
        $table->editColumn('requester', static fn (BookingAdjustmentRequest $row): string => e(ActorType::tryFrom($row->requester_type)?->label() ?? $row->requester_type));
        $table->editColumn('amount', fn (BookingAdjustmentRequest $row): string => e($this->money($row->amount_minor, $row->currency)));
        $table->addColumn('new_total', fn (BookingAdjustmentRequest $row): string => e($this->money($row->new_total_minor, $row->currency)));
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'created_at');

        $this->actionsColumn($table, static fn (BookingAdjustmentRequest $row): array => [
            TableAction::view(route('admin.booking-adjustments.show', $row->uuid), $row),
            TableAction::confirm(
                __('admin.booking_adjustments.actions.void'),
                route('admin.booking-adjustments.void', $row->uuid),
                'void',
                $row,
                icon: 'x-mark',
                variant: 'warning',
                reasonRequired: true,
                confirm: ['title' => __('admin.booking_adjustments.confirm.void_title'), 'message' => __('admin.booking_adjustments.confirm.void_message'), 'confirm_label' => __('admin.booking_adjustments.actions.void')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }

    private function money(int $minor, string $currency): string
    {
        return app(MoneyFormatter::class)->format(Money::of($minor, app(CurrencyRegistry::class)->get($currency)));
    }
}
