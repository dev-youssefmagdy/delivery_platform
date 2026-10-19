<?php

namespace App\DTOs\Demo;

use App\Models\DemoItem;

/**
 * Read model for the Demo Item show/edit/quick-view screens.
 */
final readonly class DemoItemDetails
{
    public function __construct(
        public DemoItem $item,
        public ?string $coverUrl,
    ) {}
}
