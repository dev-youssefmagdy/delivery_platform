<?php

namespace App\DTOs\Content;

use App\DTOs\Concerns\NormalizesInput;
use App\Enums\Content\MenuLocation;
use App\Http\Requests\Admin\Content\SaveMenuRequest;

final readonly class MenuData implements ContentData
{
    use NormalizesInput;

    /**
     * @param array<string, string|null> $title
     * @param list<array{label: array<string, string>, url: string, new_tab: bool}> $items
     */
    public function __construct(
        public MenuLocation $location,
        public array $title,
        public array $items,
        public bool $fallbackToAr,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(SaveMenuRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $items = [];

        foreach ((array) ($data['items'] ?? []) as $row) {
            $row = (array) $row;
            $items[] = [
                'label' => self::filled(self::translations(['ar' => $row['label_ar'] ?? null, 'en' => $row['label_en'] ?? null])),
                'url' => trim((string) ($row['url'] ?? '')),
                'new_tab' => (bool) ($row['new_tab'] ?? false),
            ];
        }

        return new self(
            location: MenuLocation::from((string) $data['location']),
            title: self::translations($data['title'] ?? []),
            items: $items,
            fallbackToAr: (bool) ($data['fallback_to_ar'] ?? true),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    public function attributes(): array
    {
        return [
            'location' => $this->location,
            'title' => self::filled($this->title),
            'items' => $this->items,
            'fallback_to_ar' => $this->fallbackToAr,
        ];
    }

    public function lockVersion(): ?int
    {
        return $this->lockVersion;
    }
}
