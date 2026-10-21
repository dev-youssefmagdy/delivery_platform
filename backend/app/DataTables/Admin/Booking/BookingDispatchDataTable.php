<?php

namespace App\DataTables\Admin\Booking;

use App\Models\DispatchRecipient;
use App\Repositories\Contracts\Dispatch\DispatchRecipientRepositoryInterface;
use App\Support\Auth\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Dispatch tab of the booking page: which providers were notified in which wave, how far away they were, whether they saw
 * the request and what they answered (SRS §10.2). Dispatch never claims to have reached everybody; this is the real list.
 */
final class BookingDispatchDataTable extends BookingTabDataTable
{
    public const TABLE_ID = 'booking-dispatch-table';

    /** @var list<array{0: int, 1: string}> */
    protected array $defaultOrder = [[0, 'asc']];

    public function __construct(private readonly DispatchRecipientRepositoryInterface $recipients)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<DispatchRecipient>
     */
    public function query(): Builder
    {
        return $this->recipients->datatableQuery($this->booking()->id);
    }

    protected function columns(): array
    {
        return [
            $this->column('wave', __('admin.bookings.dispatch.wave'), orderable: true),
            $this->column('provider', __('admin.offers.fields.provider')),
            $this->column('distance_m', __('admin.bookings.dispatch.distance'), orderable: true),
            $this->column('notified_at', __('admin.bookings.dispatch.notified'), orderable: true),
            $this->column('seen_at', __('admin.bookings.dispatch.seen')),
            $this->column('response', __('admin.bookings.dispatch.response'), orderable: true),
            $this->column('responded_at', __('admin.bookings.dispatch.responded')),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['wave', 'provider', 'distance_m', 'notified_at', 'seen_at', 'response', 'responded_at']);

        $this->userColumn($table, 'provider', static fn (DispatchRecipient $recipient): ?array => [
            $recipient->providerProfile->user->name ?? __('admin.providers.unnamed'),
            $recipient->providerProfile->user->phone_e164 === null ? null : PhoneNumber::mask($recipient->providerProfile->user->phone_e164),
        ]);
        $table->editColumn('distance_m', static fn (Model $row): string => $row instanceof DispatchRecipient ? number_format($row->distance_m / 1000, 1).' '.__('admin.bookings.dispatch.km') : '');
        $this->dateColumn($table, 'notified_at');
        $this->dateColumn($table, 'seen_at');
        $this->dateColumn($table, 'responded_at');
        $this->enumBadgeColumn($table, 'response');
    }
}
