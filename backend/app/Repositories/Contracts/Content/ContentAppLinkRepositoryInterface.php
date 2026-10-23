<?php

namespace App\Repositories\Contracts\Content;

use App\DTOs\Content\ContentFilterData;
use App\Models\ContentAppLink;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends BaseRepositoryInterface<ContentAppLink>
 */
interface ContentAppLinkRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<ContentAppLink>
     */
    public function datatableQuery(ContentFilterData $filters): Builder;
}
