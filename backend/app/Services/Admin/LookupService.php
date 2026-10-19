<?php

namespace App\Services\Admin;

use App\DTOs\Admin\LookupOptionData;
use App\DTOs\Admin\LookupQueryData;
use App\Support\Admin\Lookups\Lookup;

/**
 * Runs a remote-select lookup: explicit ids are resolved first (selected values), then the free-text search,
 * capped at the configured limit and de-duplicated.
 */
final class LookupService
{
    /**
     * @return list<array{id: int|string, text: string, meta: array<string, scalar|null>}>
     */
    public function search(Lookup $lookup, LookupQueryData $query): array
    {
        $options = $query->ids !== [] ? $lookup->resolve(array_slice($query->ids, 0, $query->limit)) : [];

        if ($query->ids === [] || $query->term !== '') {
            $options = array_merge($options, $lookup->search($query->term, $query->limit));
        }

        $unique = [];

        foreach ($options as $option) {
            $unique[(string) $option->id] ??= $option;
        }

        return array_values(array_map(
            static fn (LookupOptionData $option): array => $option->toArray(),
            array_slice($unique, 0, $query->limit),
        ));
    }
}
