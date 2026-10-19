<?php

namespace App\Repositories\Eloquent\Demo;

use App\DTOs\Demo\DemoItemFilterData;
use App\Enums\Demo\DemoItemStatus;
use App\Models\DemoItem;
use App\Repositories\Contracts\Demo\DemoItemRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends BaseRepository<DemoItem>
 */
final class DemoItemRepository extends BaseRepository implements DemoItemRepositoryInterface
{
    public function __construct(DemoItem $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(DemoItemFilterData $filters): Builder
    {
        return $this->query()
            ->with('country:id,iso2,name')
            ->when($filters->search, static function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner
                    ->where('code', 'like', $like)
                    ->orWhere('name->ar', 'like', $like)
                    ->orWhere('name->en', 'like', $like));
            })
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn(
                'status',
                array_map(static fn (DemoItemStatus $status): string => $status->value, $filters->statuses),
            ))
            ->when($filters->type, static fn (Builder $query, $type) => $query->where('type', $type->value))
            ->when($filters->featured !== null, static fn (Builder $query) => $query->where('is_featured', $filters->featured))
            ->when($filters->countryIds !== [], static fn (Builder $query) => $query->whereIn('country_id', $filters->countryIds))
            ->when($filters->priceMin !== null, static fn (Builder $query) => $query->where('price_minor', '>=', $filters->priceMin))
            ->when($filters->priceMax !== null, static fn (Builder $query) => $query->where('price_minor', '<=', $filters->priceMax))
            ->when($filters->createdFrom, static fn (Builder $query, $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($filters->createdTo, static fn (Builder $query, $to) => $query->where('created_at', '<=', $to->endOfDay()));
    }

    public function countByStatus(): array
    {
        /** @var array<string, int> $counts */
        $counts = $this->query()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(static fn (mixed $count): int => (int) $count)
            ->all();

        return $counts;
    }

    public function loadDisplayRelations(DemoItem $demoItem): DemoItem
    {
        return $demoItem->loadMissing('country');
    }
}
