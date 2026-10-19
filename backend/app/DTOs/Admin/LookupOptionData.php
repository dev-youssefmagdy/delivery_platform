<?php

namespace App\DTOs\Admin;

/**
 * One option returned by /admin/lookups/{lookup} (Tom Select: value = id, text = label).
 */
final readonly class LookupOptionData
{
    /**
     * @param array<string, scalar|null> $meta extra non-sensitive display data
     */
    public function __construct(
        public int|string $id,
        public string $text,
        public array $meta = [],
    ) {}

    /**
     * @return array{id: int|string, text: string, meta: array<string, scalar|null>}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'text' => $this->text, 'meta' => $this->meta];
    }
}
