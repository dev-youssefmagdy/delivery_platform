<?php

namespace App\Repositories\Contracts\Content;

use App\Enums\Content\MenuLocation;
use App\Models\ContentMenu;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends ContentRepositoryInterface<ContentMenu>
 */
interface MenuRepositoryInterface extends ContentRepositoryInterface
{
    /**
     * Published menus of a location other than $exceptId (publishing a menu replaces them). Locked FOR UPDATE.
     *
     * @return Collection<int, ContentMenu>
     */
    public function lockPublishedAt(MenuLocation $location, int $exceptId): Collection;
}
