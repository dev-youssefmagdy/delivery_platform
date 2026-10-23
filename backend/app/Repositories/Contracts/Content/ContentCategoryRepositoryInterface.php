<?php

namespace App\Repositories\Contracts\Content;

use App\DTOs\Content\ContentFilterData;
use App\Enums\Content\CategoryKind;
use App\Models\ContentCategory;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends BaseRepositoryInterface<ContentCategory>
 */
interface ContentCategoryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<ContentCategory>
     */
    public function datatableQuery(ContentFilterData $filters): Builder;

    /**
     * Active categories of a kind for selects: [id => name in the current locale].
     *
     * @return array<int, string>
     */
    public function options(CategoryKind $kind): array;

    /**
     * Number of FAQs / articles in the category (a used category cannot be deleted).
     */
    public function usageCount(ContentCategory $category): int;
}
