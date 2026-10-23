<?php

namespace App\Repositories\Eloquent\Content;

use App\DTOs\Content\ContentFilterData;
use App\Models\ContentArticle;
use App\Models\ContentBanner;
use App\Models\ContentMedia;
use App\Models\ContentPage;
use App\Repositories\Contracts\Content\ContentMediaRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<ContentMedia>
 */
final class ContentMediaRepository extends BaseRepository implements ContentMediaRepositoryInterface
{
    public function __construct(ContentMedia $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(ContentFilterData $filters): Builder
    {
        return $this->query()
            ->when($filters->search, static function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner
                    ->where('original_name', 'like', $like)
                    ->orWhere('alt->ar', 'like', $like)
                    ->orWhere('alt->en', 'like', $like));
            })
            ->when($filters->updatedFrom, static fn (Builder $query, $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($filters->updatedTo, static fn (Builder $query, $to) => $query->where('created_at', '<=', $to->endOfDay()));
    }

    public function lookup(string $term, int $limit): Collection
    {
        return $this->query()
            ->when($term !== '', static fn (Builder $query) => $query->where('original_name', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function findManyByIds(array $ids): Collection
    {
        return $this->query()->whereIn('id', $ids)->get();
    }

    public function referenceCount(ContentMedia $media): int
    {
        return ContentPage::query()->where('hero_media_id', $media->id)->orWhere('og_image_id', $media->id)->count()
            + ContentArticle::query()->where('cover_media_id', $media->id)->orWhere('og_image_id', $media->id)->count()
            + ContentBanner::query()->where('media_id', $media->id)->count();
    }
}
