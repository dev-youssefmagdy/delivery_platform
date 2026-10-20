<?php

namespace App\DTOs\Provider;

/**
 * Approval of an application: which of the requested services are approved, and an optional note.
 */
final readonly class ApproveApplicationData
{
    /**
     * @param list<int> $approvedServiceCategoryIds
     */
    public function __construct(
        public array $approvedServiceCategoryIds,
        public ?string $note = null,
    ) {}
}
