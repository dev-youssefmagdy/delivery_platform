<?php

namespace App\Support\Admin\Lookups;

use App\Enums\Content\CategoryKind;

final class ArticleCategoryLookup extends ContentCategoryLookup
{
    protected function kind(): CategoryKind
    {
        return CategoryKind::Article;
    }
}
