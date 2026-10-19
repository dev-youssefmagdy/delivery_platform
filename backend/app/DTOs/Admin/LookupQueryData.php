<?php

namespace App\DTOs\Admin;

use App\Http\Requests\Admin\LookupRequest;

/**
 * A lookup search: free text `q` and/or explicit `ids[]` (to resolve labels of preselected values).
 */
final readonly class LookupQueryData
{
    /**
     * @param list<int|string> $ids
     */
    public function __construct(
        public string $term = '',
        public array $ids = [],
        public int $limit = 20,
    ) {}

    public static function fromRequest(LookupRequest $request): self
    {
        /** @var array{q?: string|null, ids?: list<int|string>|null} $data */
        $data = $request->validated();

        return new self(
            trim((string) ($data['q'] ?? '')),
            $data['ids'] ?? [],
            (int) config('admin.lookup_limit', 20),
        );
    }
}
