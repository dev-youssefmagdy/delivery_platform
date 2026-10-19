<?php

namespace App\DTOs\Contracts;

use Illuminate\Http\Request;

/**
 * Listing filters read from `filters[...]` (the filters card, Plan §5.4). Implementations are `final readonly`
 * classes that cast every value (enums, dates, numbers) and ignore unknown keys.
 */
interface FilterData
{
    public static function fromRequest(Request $request): static;

    /**
     * Number of active filters (shown in the filters card badge).
     */
    public function activeCount(): int;

    /**
     * Raw `filters[...]` input shape (enum values, Y-m-d dates) so the filters can be replayed later, e.g. by a
     * queued export: `fromRequest(Request::create('/', 'GET', ['filters' => $data->toArray()]))`.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
