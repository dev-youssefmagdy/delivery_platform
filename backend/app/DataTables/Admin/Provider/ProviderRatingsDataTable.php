<?php

namespace App\DataTables\Admin\Provider;

use App\Enums\Common\ActorType;
use App\Enums\Reviews\ModerationStatus;
use App\Models\BookingRating;
use App\Repositories\Contracts\Booking\RatingRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Ratings tab of the provider page: ratings the provider RECEIVED from customers, with the booking they belong to and
 * their moderation status; the action opens the review in the Reviews module (reviews.view) where it can be moderated.
 */
final class ProviderRatingsDataTable extends ProviderTabDataTable
{
    public const TABLE_ID = 'provider-ratings-table';

    /** @var list<array{0: int, 1: string}> */
    protected array $defaultOrder = [[4, 'desc']];

    public function __construct(private readonly RatingRepositoryInterface $ratings)
    {
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
        return $this->ratings->datatableQueryForRatee(ActorType::Provider->value, $this->provider()->id);
    }

    protected function columns(): array
    {
        return [
            $this->column('booking_code', __('admin.bookings.fields.code')),
            $this->column('stars', __('admin.ratings.fields.stars'), orderable: true, name: 'booking_ratings.stars'),
            $this->column('tags', __('admin.ratings.fields.tags')),
            $this->column('comment', __('admin.ratings.fields.comment')),
            $this->column('created_at', __('admin.ratings.fields.created'), orderable: true, name: 'booking_ratings.created_at'),
            $this->column('moderation_status', __('admin.ratings.fields.moderation')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['booking_code', 'stars', 'tags', 'comment', 'created_at', 'moderation_status', 'actions']);

        $table->editColumn('booking_code', static fn (Model $row): string => '<a href="'.e(route('admin.bookings.show', (string) $row->getAttribute('booking_uuid'))).'" class="font-medium text-brand-700 hover:underline ltr-only">'.e((string) $row->getAttribute('booking_code')).'</a>');
        $this->rawColumn('booking_code');
        $table->editColumn('stars', static fn (Model $row): string => str_repeat('★', (int) $row->getAttribute('stars')).str_repeat('☆', 5 - (int) $row->getAttribute('stars')));
        $table->editColumn('tags', static fn (Model $row): string => $row instanceof BookingRating ? e(implode(', ', (array) ($row->tags ?? []))) : '');
        $table->editColumn('comment', static fn (Model $row): string => $row instanceof BookingRating && $row->comment !== null ? Str::limit($row->comment, 160) : '—');
        $this->dateColumn($table, 'created_at');
        $table->editColumn('moderation_status', static function (Model $row): string {
            $status = $row instanceof BookingRating ? $row->moderation_status : ModerationStatus::Visible;

            return Blade::renderComponent(new Badge(label: $status->label(), color: $status === ModerationStatus::Visible ? 'success' : 'warning'));
        });
        $this->rawColumn('moderation_status');
        $this->actionsColumn($table, static fn (BookingRating $rating): array => [TableAction::view(route('admin.reviews.show', $rating), $rating)]);
    }
}
