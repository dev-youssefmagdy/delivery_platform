<?php

namespace App\DataTables\Admin\Coupon;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Coupon\CouponFilterData;
use App\Enums\Admin\AdminPermission;
use App\Models\Coupon;
use App\Repositories\Contracts\Coupon\CouponRepositoryInterface;
use App\Services\Coupon\CouponDefinitionFactory;
use App\Support\Admin\Tables\TableAction;
use App\Support\Coupon\CouponPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Coupons listing (Appendix C filters: code, status, discount type, funding source, service family, active on a date, validity range).
 */
final class CouponsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'coupons-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly CouponRepositoryInterface $coupons)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    public function exportPermission(): ?AdminPermission
    {
        return AdminPermission::CouponsExport;
    }

    /**
     * @return Builder<Coupon>
     */
    public function query(): Builder
    {
        return $this->coupons->datatableQuery(CouponFilterData::fromRequest($this->filterRequest()))->with('scopes');
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('code', __('admin.coupons.fields.code'), orderable: true),
            $this->column('name', __('admin.coupons.fields.name'), orderable: true),
            $this->column('discount', __('admin.coupons.fields.discount')),
            $this->column('funding', __('admin.coupons.fields.funding_source')),
            $this->column('usage', __('admin.coupons.fields.usage')),
            $this->column('budget', __('admin.coupons.fields.budget')),
            $this->column('starts_at', __('admin.coupons.fields.starts_at'), orderable: true),
            $this->column('ends_at', __('admin.coupons.fields.ends_at'), orderable: true),
            $this->column('status', __('admin.coupons.fields.status'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    /**
     * CSV row: the computed columns (discount, funding, usage, budget) are not attributes, so they are rebuilt here.
     *
     * @return list<string>
     */
    public function exportRow(Model $row): array
    {
        if (! $row instanceof Coupon) {
            return [];
        }

        $presenter = app(CouponPresenter::class);
        $terms = app(CouponDefinitionFactory::class)->termsOf($row);

        return [
            (string) $row->id,
            $row->code ?? (string) __('admin.coupons.automatic'),
            $row->name,
            $presenter->discount($terms, $row->currency),
            $presenter->funding($terms),
            $row->used_count.' / '.($row->global_limit ?? '-'),
            $row->budget_minor === null ? '' : ($row->spent_minor / 100).' / '.($row->budget_minor / 100).' '.$row->currency,
            $row->starts_at === null ? '' : $this->stringify($row->starts_at),
            $row->ends_at === null ? '' : $this->stringify($row->ends_at),
            $row->status->label(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $presenter = app(CouponPresenter::class);
        $definitions = app(CouponDefinitionFactory::class);

        $table->editColumn('code', static fn (Model $row): string => $row instanceof Coupon ? e($row->code ?? __('admin.coupons.automatic')) : '');
        $table->addColumn('discount', static fn (Coupon $coupon): string => e($presenter->discount($definitions->termsOf($coupon), $coupon->currency)));
        $this->rawColumn('discount');
        $table->addColumn('funding', static fn (Coupon $coupon): string => e($presenter->funding($definitions->termsOf($coupon))));
        $this->rawColumn('funding');
        $table->addColumn('usage', static fn (Coupon $coupon): string => $coupon->used_count.($coupon->reserved_count > 0 ? ' (+'.$coupon->reserved_count.')' : '').' / '.($coupon->global_limit ?? '∞'));
        $table->addColumn('budget', static fn (Coupon $coupon): string => $coupon->budget_minor === null
            ? '—'
            : e($presenter->money($coupon->spent_minor, $coupon->currency).' / '.$presenter->money($coupon->budget_minor, $coupon->currency)));
        $this->rawColumn('budget');
        $table->editColumn('starts_at', fn (Model $row): string => $row instanceof Coupon && $row->starts_at !== null ? $this->formatDate($row->starts_at) : '—');
        $table->editColumn('ends_at', fn (Model $row): string => $row instanceof Coupon && $row->ends_at !== null ? $this->formatDate($row->ends_at) : '—');
        $this->enumBadgeColumn($table, 'status');

        $this->actionsColumn($table, static fn (Coupon $coupon): array => [
            TableAction::view(route('admin.coupons.show', $coupon), $coupon),
            TableAction::edit(route('admin.coupons.edit', $coupon), $coupon),
            TableAction::confirm(
                __('admin.coupons.actions.publish'),
                route('admin.coupons.publish', $coupon),
                'publish',
                $coupon,
                icon: 'play',
                variant: 'success',
                confirm: ['title' => __('admin.coupons.confirm.publish_title'), 'message' => __('admin.coupons.confirm.publish_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.coupons.actions.resume'),
                route('admin.coupons.resume', $coupon),
                'resume',
                $coupon,
                icon: 'play',
                variant: 'success',
                confirm: ['title' => __('admin.coupons.confirm.resume_title'), 'message' => __('admin.coupons.confirm.resume_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.coupons.actions.pause'),
                route('admin.coupons.pause', $coupon),
                'pause',
                $coupon,
                icon: 'pause',
                reasonRequired: true,
                confirm: ['title' => __('admin.coupons.confirm.pause_title'), 'message' => __('admin.coupons.confirm.pause_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.coupons.actions.archive'),
                route('admin.coupons.archive', $coupon),
                'archive',
                $coupon,
                icon: 'archive',
                reasonRequired: true,
                confirm: ['title' => __('admin.coupons.confirm.archive_title'), 'message' => __('admin.coupons.confirm.archive_message')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }
}
