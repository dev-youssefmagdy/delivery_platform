<?php

namespace App\Support\Admin\Lookups;

use App\DTOs\Admin\LookupOptionData;
use App\Enums\Admin\AdminPermission;
use App\Enums\Content\CategoryKind;
use App\Models\ContentCategory;
use App\Repositories\Contracts\Content\ContentCategoryRepositoryInterface;

/**
 * Active FAQ / article categories for the CMS forms and filters. Subclasses fix the kind.
 */
abstract class ContentCategoryLookup implements Lookup
{
    public function __construct(private readonly ContentCategoryRepositoryInterface $categories) {}

    abstract protected function kind(): CategoryKind;

    public function permission(): ?AdminPermission
    {
        return AdminPermission::ContentManage;
    }

    public function search(string $term, int $limit): array
    {
        return $this->map($this->categories->lookup($this->kind(), $term, $limit)->all());
    }

    public function resolve(array $ids): array
    {
        return $this->map($this->categories->findManyByIds($this->kind(), $ids)->all());
    }

    /**
     * @param array<int, ContentCategory> $items
     * @return list<LookupOptionData>
     */
    private function map(array $items): array
    {
        return array_values(array_map(
            static fn (ContentCategory $category): LookupOptionData => new LookupOptionData($category->id, (string) $category->name),
            $items,
        ));
    }
}
