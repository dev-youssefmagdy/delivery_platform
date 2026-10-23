<?php

namespace App\Repositories\Eloquent\Content;

use App\DTOs\Content\ContentFilterData;
use App\Enums\Content\ContentWorkflowStatus;
use App\Enums\Content\LocaleCompleteness;
use App\Repositories\Eloquent\BaseRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Behaviour shared by the workflow entities: status / locale / date filters and the due-scheduled query.
 *
 * @template TModel of Model
 *
 * @extends BaseRepository<TModel>
 */
abstract class ContentRepository extends BaseRepository
{
    /**
     * JSON columns searched by the free-text filter (ar and en).
     *
     * @return list<string>
     */
    abstract protected function searchColumns(): array;

    /**
     * The JSON column checked by the "locale completeness" filter.
     */
    abstract protected function completenessColumn(): string;

    /**
     * @param Builder<TModel> $query
     * @return Builder<TModel>
     */
    protected function applyCommonFilters(Builder $query, ContentFilterData $filters): Builder
    {
        $completenessColumn = $this->completenessColumn();

        return $query
            ->when($filters->search, function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function (Builder $inner) use ($like): void {
                    foreach ($this->searchColumns() as $column) {
                        $inner->orWhere($column.'->ar', 'like', $like)->orWhere($column.'->en', 'like', $like);
                    }
                });
            })
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn(
                'status',
                array_map(static fn (ContentWorkflowStatus $status): string => $status->value, $filters->statuses),
            ))
            ->when($filters->completeness === LocaleCompleteness::Complete, static fn (Builder $query) => $query
                ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`{$completenessColumn}`, '$.en')), '') <> ''"))
            ->when($filters->completeness === LocaleCompleteness::ArabicOnly, static fn (Builder $query) => $query
                ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`{$completenessColumn}`, '$.en')), '') = ''"))
            ->when($filters->updatedFrom, static fn (Builder $query, $from) => $query->where('updated_at', '>=', $from->startOfDay()))
            ->when($filters->updatedTo, static fn (Builder $query, $to) => $query->where('updated_at', '<=', $to->endOfDay()));
    }

    public function dueScheduledIds(CarbonInterface $now): array
    {
        /** @var list<int> $ids */
        $ids = $this->query()
            ->where('status', ContentWorkflowStatus::Scheduled->value)
            ->where('scheduled_at', '<=', $now)
            ->orderBy('scheduled_at')
            ->limit(500)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        return $ids;
    }

    public function loadRelations(Model $model, array $relations): Model
    {
        return $model->loadMissing($relations);
    }
}
