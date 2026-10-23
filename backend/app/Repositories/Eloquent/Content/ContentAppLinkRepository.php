<?php

namespace App\Repositories\Eloquent\Content;

use App\DTOs\Content\ContentFilterData;
use App\Models\ContentAppLink;
use App\Repositories\Contracts\Content\ContentAppLinkRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends BaseRepository<ContentAppLink>
 */
final class ContentAppLinkRepository extends BaseRepository implements ContentAppLinkRepositoryInterface
{
    public function __construct(ContentAppLink $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(ContentFilterData $filters): Builder
    {
        return $this->query()
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('active', $filters->active));
    }
}
