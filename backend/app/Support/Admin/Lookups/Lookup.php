<?php

namespace App\Support\Admin\Lookups;

use App\DTOs\Admin\LookupOptionData;
use App\Enums\Admin\AdminPermission;

/**
 * A remote-select data source registered in config('admin.lookups'). Implementations delegate to a
 * repository method and never return more than $limit options.
 */
interface Lookup
{
    /**
     * Permission required to use the lookup; null = any admin (still behind admin authentication).
     */
    public function permission(): ?AdminPermission;

    /**
     * @return list<LookupOptionData>
     */
    public function search(string $term, int $limit): array;

    /**
     * Labels for already selected ids (edit forms, URL-restored filters).
     *
     * @param list<int|string> $ids
     * @return list<LookupOptionData>
     */
    public function resolve(array $ids): array;
}
