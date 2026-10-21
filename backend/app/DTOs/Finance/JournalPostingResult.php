<?php

namespace App\DTOs\Finance;

use App\Models\JournalEntry;

/**
 * Outcome of a post: the entry and whether it already existed for the idempotency key (a replay).
 */
final readonly class JournalPostingResult
{
    public function __construct(public JournalEntry $entry, public bool $replayed = false) {}
}
