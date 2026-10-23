<?php

namespace App\DataTables\Admin\Reviews;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Reviews\ReviewFilterData;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\ActorType;
use App\Enums\Reviews\ModerationStatus;
use App\Models\BookingRating;
use App\Repositories\Contracts\Booking\RatingRepositoryInterface;
use App\Repositories\Contracts\Reviews\RatingModerationRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Reviews listing (Plan Appendix C "Reviews": stars range, moderation status, reported only, ratee type, date range, booking code).
 * Shows what people wrote; there is no edit action anywhere (SRS §16.12), only the show page with hide / restore.
 */
final class ReviewsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'reviews-table';

    protected array $defaultOrder = [[7, 'desc']];

    protected bool $globalSearch = false;

    /** @var array<string, string> */
    private array $names = [];

    public function __construct(
        private readonly RatingRepositoryInterface $ratings,
        private readonly RatingModerationRepositoryInterface $moderation,
    ) {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<BookingRating>
     */
    public function query(): Builder
    {
        return $this->ratings->datatableQuery(ReviewFilterData::fromRequest($this->filterRequest()));
    }

    public function exportPermission(): ?AdminPermission
    {
        return null;
    }

    protected function columns(): array
    {
        return [
            $this->column('booking_code', __('admin.bookings.fields.code')),
            $this->column('stars', __('admin.ratings.fields.stars'), orderable: true, name: 'booking_ratings.stars'),
            $this->column('comment', __('admin.ratings.fields.comment')),
            $this->column('rater', __('reputation.admin.fields.rater')),
            $this->column('ratee', __('reputation.admin.fields.ratee')),
            $this->column('moderation_status', __('admin.ratings.fields.moderation'), orderable: true, name: 'booking_ratings.moderation_status'),
            $this->column('reports_count', __('reputation.admin.fields.reports'), orderable: true, name: 'booking_ratings.reports_count'),
            $this->column('created_at', __('admin.ratings.fields.created'), orderable: true, name: 'booking_ratings.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['booking_code', 'stars', 'comment', 'rater', 'ratee', 'moderation_status', 'reports_count', 'created_at', 'actions']);

        $table->editColumn('booking_code', static fn (Model $row): string => '<a href="'.e(route('admin.bookings.show', (string) $row->getAttribute('booking_uuid'))).'" class="font-medium text-brand-700 hover:underline ltr-only">'.e((string) $row->getAttribute('booking_code')).'</a>');
        $this->rawColumn('booking_code');
        $table->editColumn('stars', static fn (Model $row): string => str_repeat('★', (int) $row->getAttribute('stars')).str_repeat('☆', 5 - (int) $row->getAttribute('stars')));
        $table->editColumn('comment', static fn (Model $row): string => $row instanceof BookingRating && $row->comment !== null && $row->comment !== '' ? Str::limit($row->comment, 120) : '—');
        $table->addColumn('rater', fn (BookingRating $rating): string => $this->party($rating->rater_type, $rating->rater_id));
        $table->addColumn('ratee', fn (BookingRating $rating): string => $this->party($rating->ratee_type, $rating->ratee_id));
        $table->editColumn('moderation_status', static function (Model $row): string {
            $status = $row instanceof BookingRating ? $row->moderation_status : ModerationStatus::Visible;

            return Blade::renderComponent(new Badge(label: $status->label(), color: match ($status) {
                ModerationStatus::Visible => 'success', ModerationStatus::Hidden => 'danger', ModerationStatus::UnderReview => 'warning',
            }));
        });
        $this->rawColumn('moderation_status');
        $this->dateColumn($table, 'created_at');

        $this->actionsColumn($table, static fn (BookingRating $rating): array => [
            TableAction::view(route('admin.reviews.show', $rating), $rating),
        ]);
    }

    private function party(string $type, int $id): string
    {
        $key = $type.':'.$id;
        $this->names[$key] ??= ($this->moderation->partyName($type, $id) ?? '—').' · '.($type === ActorType::Provider->value ? __('reputation.enums.subject.provider') : __('reputation.enums.subject.customer'));

        return $this->names[$key];
    }
}
