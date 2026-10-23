<?php

namespace App\DTOs\Content;

use App\DTOs\Concerns\NormalizesInput;
use App\Enums\Content\CategoryKind;
use App\Http\Requests\Admin\Content\SaveCategoryRequest;

final readonly class CategoryData
{
    use NormalizesInput;

    /**
     * @param array<string, string|null> $name
     * @param array<string, string|null> $description
     */
    public function __construct(
        public CategoryKind $kind,
        public string $slug,
        public array $name,
        public array $description,
        public int $sortOrder,
        public bool $active,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(SaveCategoryRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        return new self(
            kind: CategoryKind::from((string) $data['kind']),
            slug: (string) $data['slug'],
            name: self::translations($data['name'] ?? []),
            description: self::translations($data['description'] ?? []),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            active: (bool) ($data['active'] ?? true),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'kind' => $this->kind,
            'slug' => $this->slug,
            'name' => self::filled($this->name),
            'description' => self::filled($this->description),
            'sort_order' => $this->sortOrder,
            'active' => $this->active,
        ];
    }
}
