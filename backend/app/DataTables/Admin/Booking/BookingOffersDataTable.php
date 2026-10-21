<?php

namespace App\DataTables\Admin\Booking;

use App\DTOs\Booking\OfferFilterData;
use App\Enums\Booking\OfferAnomaly;
use App\Models\BookingOffer;
use App\Models\BookingOfferVersion;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use App\Support\Auth\PhoneNumber;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\MoneyFormatter;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;

/**
 * Offers tab of the booking page: every offer with ALL of its versions (amount, note, time), exactly as submitted. Nothing
 * here can edit a version: they are append-only.
 */
final class BookingOffersDataTable extends BookingTabDataTable
{
    public const TABLE_ID = 'booking-offers-table';

    /** @var list<array{0: int, 1: string}> */
    protected array $defaultOrder = [[5, 'asc']];

    public function __construct(private readonly OfferRepositoryInterface $offers)
    {
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
        return $this->offers->adminDatatableQuery(OfferFilterData::forBooking($this->booking()->id))->with('versions');
    }

    protected function columns(): array
    {
        return [
            $this->column('provider', __('admin.offers.fields.provider')),
            $this->column('kind', __('admin.offers.fields.kind')),
            $this->column('status', __('admin.offers.fields.status'), orderable: true, name: 'booking_offers.status'),
            $this->column('amount', __('admin.offers.fields.amount'), orderable: true, name: 'current_version.amount_minor'),
            $this->column('anomaly', __('admin.offers.fields.anomaly')),
            $this->column('created_at', __('admin.offers.fields.created'), orderable: true, name: 'booking_offers.created_at'),
            $this->column('versions', __('admin.offers.fields.versions')),
            $this->column('close_reason', __('admin.offers.fields.close_reason')),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['provider', 'kind', 'status', 'amount', 'anomaly', 'created_at', 'versions', 'close_reason']);

        $this->userColumn($table, 'provider', static fn (BookingOffer $offer): ?array => [
            $offer->providerProfile->user->name ?? __('admin.providers.unnamed'),
            $offer->providerProfile->user->phone_e164 === null ? null : PhoneNumber::mask($offer->providerProfile->user->phone_e164),
        ]);
        $table->editColumn('kind', static fn (Model $row): string => $row instanceof BookingOffer ? e($row->kind->label()) : '');
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('amount', function (Model $row): string {
            $minor = $row->getAttribute('amount_minor');

            return $minor === null ? '—' : $this->money((int) $minor, (string) $row->getAttribute('booking_currency'));
        });
        $table->editColumn('anomaly', static function (Model $row): string {
            $anomaly = OfferAnomaly::tryFrom((string) $row->getAttribute('anomaly'));

            return $anomaly === null ? '—' : Blade::renderComponent(new Badge($anomaly));
        });
        $this->rawColumn('anomaly');
        $this->dateColumn($table, 'created_at');

        $table->addColumn('versions', function (Model $row): string {
            if (! $row instanceof BookingOffer) {
                return '';
            }

            $lines = $row->versions->map(fn (BookingOfferVersion $version): string => '<li class="whitespace-normal">'
                .'<span class="font-medium">v'.$version->version.'</span> · '.e($this->money($version->amount_minor, $version->currency))
                .' <span class="text-xs text-slate-500">'.e($this->formatDate($version->created_at)).'</span>'
                .($version->note !== null && $version->note !== '' ? '<span class="block text-xs text-slate-600">'.e($version->note).'</span>' : '')
                .'</li>');

            return '<ul class="space-y-1 text-sm">'.$lines->implode('').'</ul>';
        });
        $this->rawColumn('versions');

        $table->editColumn('close_reason', static fn (Model $row): string => $row->getAttribute('close_reason') === null ? '—' : e((string) $row->getAttribute('close_reason')));
    }

    private function money(int $minor, string $currency): string
    {
        $registry = app(CurrencyRegistry::class);

        return app(MoneyFormatter::class)->format(\App\Support\Money\Money::of($minor, $registry->get($currency)));
    }
}
