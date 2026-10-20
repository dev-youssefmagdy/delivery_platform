<?php

namespace App\DTOs\Catalog;

use App\DTOs\Geography\ResolvedArea;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Collection;

/**
 * Public category list plus, when a position was given, the area it was resolved in.
 */
final readonly class CategoryListing
{
    /**
     * @param Collection<int, ServiceCategory> $categories
     */
    public function __construct(public Collection $categories, public ?ResolvedArea $area) {}
}
