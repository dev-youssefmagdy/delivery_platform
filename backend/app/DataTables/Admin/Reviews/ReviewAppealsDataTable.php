<?php

namespace App\DataTables\Admin\Reviews;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Reviews\ModerationQueueFilterData;
use App\Models\RatingAppeal;
use App\Repositories\Contracts\Reviews\RatingModerationRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Appeals tab of the Reviews module: open appeals first by age; the decision is taken on the review page (with the full context).
 */
final class ReviewAppealsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'review-appeals-table';

    protected array $defaultOrder = [[4, 'asc']];

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
     * @return Builder<RatingAppeal>
     */
    public function query(): Builder
    {
        return $this->moderation->appealsDatatableQuery(ModerationQueueFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('booking_code', __('admin.bookings.fields.code')),
            $this->column('grounds', __('reputation.admin.fields.grounds')),
            $this->column('appellant_type', __('reputation.admin.fields.appellant')),
            $this->column('status', __('reputation.admin.fields.status'), orderable: true, name: 'rating_appeals.status'),
            $this->column('created_at', __('admin.ratings.fields.created'), orderable: true, name: 'rating_appeals.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['booking_code', 'grounds', 'appellant_type', 'status', 'created_at', 'actions']);
        $table->editColumn('booking_code', static fn (Model $row): string => e((string) $row->getAttribute('booking_code')));
        $table->editColumn('grounds', static fn (Model $row): string => $row instanceof RatingAppeal ? e($row->grounds->label()) : '');
        $table->editColumn('appellant_type', static fn (Model $row): string => $row instanceof RatingAppeal ? e(__('reputation.enums.subject.'.$row->appellant_type)) : '');
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'created_at');
        $this->actionsColumn($table, static fn (RatingAppeal $appeal): array => [
            new TableAction(__('admin.common.view'), route('admin.reviews.show', (string) $appeal->getAttribute('rating_uuid')), icon: 'eye', ability: 'viewAny', subject: RatingAppeal::class),
        ]);
    }
}
