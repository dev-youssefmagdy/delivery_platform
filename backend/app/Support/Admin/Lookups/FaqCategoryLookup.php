<?php

namespace App\Support\Admin\Lookups;

use App\Enums\Content\CategoryKind;

final class FaqCategoryLookup extends ContentCategoryLookup
{
    protected function kind(): CategoryKind
    {
        return CategoryKind::Faq;
    }
}
