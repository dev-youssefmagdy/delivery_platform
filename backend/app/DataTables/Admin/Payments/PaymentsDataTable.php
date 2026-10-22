<?php

namespace App\DataTables\Admin\Payments;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Payments\PaymentFilterData;
use App\Models\Payment;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Auth\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Payments listing (filters: status, kind, method, gateway, booking code or reference, created range, amount range).
 * Read-only: the panel never edits a payment.
 */
class PaymentsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'payments-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly PaymentRepositoryInterface $payments)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return static::TABLE_ID;
    }

    /**
     * @return Builder<Payment>
     */
    public function query(): Builder
    {
        return $this->payments->datatableQuery($this->scoped(PaymentFilterData::fromRequest($this->filterRequest())));
    }

    /**
     * Tab subclasses narrow the listing to their booking or customer.
     */
    protected function scoped(PaymentFilterData $filters): PaymentFilterData
    {
        return $filters;
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'payments.id'),
            $this->column('payment', __('admin.payments.fields.payment')),
            $this->column('booking', __('admin.payments.fields.booking')),
            $this->column('customer', __('admin.payments.fields.customer')),
            $this->column('kind', __('admin.payments.fields.kind')),
            $this->column('method_type', __('admin.payments.fields.method')),
            $this->column('gateway', __('admin.payments.fields.gateway')),
            $this->column('status', __('admin.payments.fields.status'), orderable: true, name: 'payments.status'),
            $this->column('amount', __('admin.payments.fields.amount'), orderable: true, name: 'payments.amount_minor'),
            $this->column('refunded', __('admin.payments.fields.refunded')),
            $this->column('created_at', __('admin.payments.fields.created'), orderable: true, name: 'payments.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'payment', 'booking', 'customer', 'kind', 'method_type', 'gateway', 'status', 'amount', 'refunded', 'created_at', 'actions']);

        $table->addColumn('payment', static fn (Payment $payment): string => '<a href="'.e(route('admin.payments.show', $payment)).'" class="ltr-only font-medium text-brand-700 hover:underline">'.e(Str::substr($payment->uuid, 0, 8)).'</a>');
        $this->rawColumn('payment');
        $table->addColumn('booking', static fn (Payment $payment): string => '<a href="'.e(route('admin.bookings.show', $payment->booking->uuid)).'" class="ltr-only text-brand-700 hover:underline">'.e($payment->booking->code).'</a>');
        $this->rawColumn('booking');
        $this->userColumn($table, 'customer', static fn (Payment $payment): ?array => [
            $payment->user->name ?? __('admin.customers.unnamed'),
            $payment->user->phone_e164 === null ? null : PhoneNumber::mask($payment->user->phone_e164),
        ]);
        $table->editColumn('kind', static fn (Model $row): string => $row instanceof Payment ? e($row->kind->label()) : '');
        $table->editColumn('method_type', static fn (Model $row): string => $row instanceof Payment ? e($row->method_type->label()) : '');
        $table->editColumn('gateway', static fn (Model $row): string => $row instanceof Payment ? e($row->gateway?->label() ?? '—') : '');
        $this->enumBadgeColumn($table, 'status');
        $this->moneyColumn($table, 'amount');
        $table->addColumn('refunded', fn (Payment $payment): string => $payment->refunded_minor === 0 ? '—' : $this->formatMoney($payment->refunded_minor, $payment->currency));
        $this->dateColumn($table, 'created_at');

        $this->actionsColumn($table, static fn (Payment $payment): array => [TableAction::view(route('admin.payments.show', $payment), $payment)]);
    }

    private function formatMoney(int $minor, string $currency): string
    {
        return app(\App\Support\Money\MoneyFormatter::class)->format(\App\Support\Money\Money::of($minor, app(\App\Support\Money\CurrencyRegistry::class)->get($currency)));
    }
}
