<?php

namespace App\DTOs\Support;

use App\Enums\Support\TicketCategory;
use Illuminate\Http\Request;

final readonly class SupportMacroData
{
    public function __construct(
        public string $titleAr,
        public ?string $titleEn,
        public string $bodyAr,
        public ?string $bodyEn,
        public ?TicketCategory $category,
        public bool $active,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $category = trim((string) $request->input('category'));
        $titleEn = trim((string) $request->input('title.en'));
        $bodyEn = trim((string) $request->input('body.en'));

        return new self(
            titleAr: trim((string) $request->input('title.ar')),
            titleEn: $titleEn === '' ? null : $titleEn,
            bodyAr: trim((string) $request->input('body.ar')),
            bodyEn: $bodyEn === '' ? null : $bodyEn,
            category: $category === '' ? null : TicketCategory::from($category),
            active: $request->boolean('active', true),
            lockVersion: $request->filled('lock_version') ? $request->integer('lock_version') : null,
        );
    }
}
