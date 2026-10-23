<?php

namespace App\DataTables\Admin\Reviews;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Reviews\ModerationQueueFilterData;
use App\Models\RatingReport;
use App\Repositories\Contracts\Reviews\RatingModerationRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Reports tab of the Reviews module: what people flagged and why. The reporter's free text is part of the review page, not of this list.
 */
final class ReviewReportsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'review-reports-table';

    protected array $defaultOrder = [[4, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly RatingModerationRepositoryInterface $moderation)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<RatingReport>
     */
    public function query(): Builder
    {
        return $this->moderation->reportsDatatableQuery(ModerationQueueFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('booking_code', __('admin.bookings.fields.code')),
            $this->column('reason', __('reputation.admin.fields.reason')),
            $this->column('reporter_type', __('reputation.admin.fields.reporter')),
            $this->column('status', __('reputation.admin.fields.status'), orderable: true, name: 'rating_reports.status'),
            $this->column('created_at', __('admin.ratings.fields.created'), orderable: true, name: 'rating_reports.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['booking_code', 'reason', 'reporter_type', 'status', 'created_at', 'actions']);
        $table->editColumn('booking_code', static fn (Model $row): string => e((string) $row->getAttribute('booking_code')));
        $table->editColumn('reason', static fn (Model $row): string => $row instanceof RatingReport ? e($row->reason->label()) : '');
        $table->editColumn('reporter_type', static fn (Model $row): string => $row instanceof RatingReport ? e(__('reputation.enums.subject.'.$row->reporter_type)) : '');
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'created_at');
        $this->actionsColumn($table, static fn (RatingReport $report): array => [
            new TableAction(__('admin.common.view'), route('admin.reviews.show', (string) $report->getAttribute('rating_uuid')), icon: 'eye', ability: 'viewAny', subject: RatingReport::class),
        ]);
    }
}
