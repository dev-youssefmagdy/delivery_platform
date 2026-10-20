<?php

namespace App\Repositories\Eloquent\Policy;

use App\DTOs\Policy\PolicyVersionFilterData;
use App\Enums\Content\ContentStatus;
use App\Enums\Policy\PolicyType;
use App\Models\PolicyVersion;
use App\Repositories\Contracts\Policy\PolicyVersionRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<PolicyVersion>
 */
final class PolicyVersionRepository extends BaseRepository implements PolicyVersionRepositoryInterface
{
    public function __construct(PolicyVersion $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(PolicyVersionFilterData $filters): Builder
    {
        return $this->query()
            ->when($filters->search, static function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner
                    ->where('title->ar', 'like', $like)
                    ->orWhere('title->en', 'like', $like));
            })
            ->when($filters->types !== [], static fn (Builder $query) => $query->whereIn(
                'type',
                array_map(static fn (PolicyType $type): string => $type->value, $filters->types),
            ))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn(
                'status',
                array_map(static fn (ContentStatus $status): string => $status->value, $filters->statuses),
            ))
            ->when($filters->publishedFrom, static fn (Builder $query, $from) => $query->where('published_at', '>=', $from->startOfDay()))
            ->when($filters->publishedTo, static fn (Builder $query, $to) => $query->where('published_at', '<=', $to->endOfDay()));
    }

    public function lockByType(PolicyType $type): Collection
    {
        return $this->query()->where('type', $type->value)->orderBy('id')->lockForUpdate()->get();
    }

    public function currentPublished(): Collection
    {
        return $this->query()
            ->where('status', ContentStatus::Published->value)
            ->orderBy('type')
            ->orderByDesc('version')
            ->get()
            ->unique(static fn (PolicyVersion $policy): string => $policy->type->value)
            ->values();
    }

    public function findCurrentByType(PolicyType $type): ?PolicyVersion
    {
        return $this->query()
            ->where('type', $type->value)
            ->where('status', ContentStatus::Published->value)
            ->orderByDesc('version')
            ->first();
    }

    public function currentPublishedByUuids(array $uuids): Collection
    {
        if ($uuids === []) {
            return new Collection;
        }

        return $this->currentPublished()
            ->filter(static fn (PolicyVersion $policy): bool => in_array($policy->uuid, $uuids, true))
            ->values();
    }

    public function loadPublisher(PolicyVersion $policy): PolicyVersion
    {
        return $policy->loadMissing('publisher:id,name,email');
    }
}
