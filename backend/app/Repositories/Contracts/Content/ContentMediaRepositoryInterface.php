<?php

namespace App\Repositories\Contracts\Content;

use App\DTOs\Content\ContentFilterData;
use App\Models\ContentMedia;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<ContentMedia>
 */
interface ContentMediaRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<ContentMedia>
     */
    public function datatableQuery(ContentFilterData $filters): Builder;

    /**
     * @return Collection<int, ContentMedia>
     */
    public function lookup(string $term, int $limit): Collection;

    /**
     * @param list<int|string> $ids
     * @return Collection<int, ContentMedia>
     */
    public function findManyByIds(array $ids): Collection;

    /**
     * How many pages, articles and banners use the image (a used image cannot be deleted).
     */
    public function referenceCount(ContentMedia $media): int;
}
