<?php

namespace App\DataTables\Admin\Booking;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Booking\OfferFilterData;
use App\Enums\Booking\OfferAnomaly;
use App\Models\BookingOffer;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use App\Services\Offers\OfferMonitorService;
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
 * Offers monitor (SRS §16.7, Plan Appendix C): every offer with its booking, provider, current amount against the
 * customer's fare, status and anomaly flag. Read-only. The JSON response also carries `stats` (conversion numbers for the
 * same filters), which the page shows in tiles and refreshes with every draw.
 */
final class OffersMonitorDataTable extends AdminDataTable
{
    public const TABLE_ID = 'offers-monitor-table';

    protected bool $globalSearch = false;

    /** @var list<array{0: int, 1: string}> */
    protected array $defaultOrder = [[8, 'desc']];

    public function __construct(
        private readonly OfferRepositoryInterface $offers,
        private readonly OfferMonitorService $monitor,
    ) {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<BookingOffer>
     */
    public function query(): Builder
    {
        return $this->offers->adminDatatableQuery($this->filters());
    }

    private function filters(): OfferFilterData
    {
        return OfferFilterData::fromRequest($this->filterRequest());
    }

    protected function columns(): array
    {
        return [
            $this->column('booking_code', __('admin.offers.fields.booking'), orderable: true, name: 'bookings.code'),
            $this->column('provider', __('admin.offers.fields.provider')),
            $this->column('kind', __('admin.offers.fields.kind'), orderable: true, name: 'booking_offers.kind'),
            $this->column('status', __('admin.offers.fields.status'), orderable: true, name: 'booking_offers.status'),
            $this->column('amount', __('admin.offers.fields.amount'), orderable: true, name: 'current_version.amount_minor'),
            $this->column('customer_fare', __('admin.offers.fields.customer_fare'), orderable: true, name: 'bookings.proposed_fare_minor'),
            $this->column('anomaly', __('admin.offers.fields.anomaly')),
            $this->column('versions', __('admin.offers.fields.versions')),
            $this->column('created_at', __('admin.offers.fields.created'), orderable: true, name: 'booking_offers.created_at'),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['booking_code', 'provider', 'kind', 'status', 'amount', 'customer_fare', 'anomaly', 'versions', 'created_at']);

        $table->editColumn('booking_code', static fn (Model $row): string => '<a href="'.e(route('admin.bookings.show', (string) $row->getAttribute('booking_uuid'))).'" class="font-medium text-brand-700 hover:underline ltr-only">'.e((string) $row->getAttribute('booking_code')).'</a>');
        $this->rawColumn('booking_code');

        $this->userColumn($table, 'provider', static fn (BookingOffer $offer): array => [
            $offer->providerProfile->user->name ?? __('admin.providers.unnamed'),
            $offer->providerProfile->user->phone_e164 === null ? null : PhoneNumber::mask($offer->providerProfile->user->phone_e164),
        ]);
        $table->editColumn('kind', static fn (Model $row): string => $row instanceof BookingOffer ? e($row->kind->label()) : '');
        $this->enumBadgeColumn($table, 'status');

        $money = static function (mixed $minor, string $currency): string {
            return $minor === null ? '—' : app(MoneyFormatter::class)->format(Money::of((int) $minor, app(CurrencyRegistry::class)->get($currency)));
        };
        $table->addColumn('amount', static fn (Model $row): string => $money($row->getAttribute('amount_minor'), (string) $row->getAttribute('booking_currency')));
        $table->addColumn('customer_fare', static fn (Model $row): string => $money($row->getAttribute('customer_fare_minor'), (string) $row->getAttribute('booking_currency')));

        $table->editColumn('anomaly', static function (Model $row): string {
            $anomaly = OfferAnomaly::tryFrom((string) $row->getAttribute('anomaly'));

            return $anomaly === null ? '—' : Blade::renderComponent(new Badge($anomaly));
        });
        $this->rawColumn('anomaly');
        $table->addColumn('versions', static fn (Model $row): int => $row instanceof BookingOffer ? $row->revision_count + 1 : 1);
        $this->dateColumn($table, 'created_at');

        $table->with('stats', function () {
            $stats = $this->monitor->stats($this->filters());

            return [
                'submitted' => number_format($stats->submitted),
                'selected' => number_format($stats->selected),
                'conversion' => $stats->conversionPercent().'%',
                'flagged' => number_format($stats->flagged),
                'response' => $stats->responsePercent().'%',
            ];
        });
    }
}
