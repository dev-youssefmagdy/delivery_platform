<?php

namespace App\DTOs\Catalog;

use App\Enums\Catalog\ServiceFamilyKind;
use App\Http\Requests\Admin\Catalog\StoreServiceFamilyRequest;
use App\Http\Requests\Admin\Catalog\UpdateServiceFamilyRequest;
use App\Models\ServiceFamily;

/**
 * Validated input for creating/updating a service family. New families start inactive; activation is a
 * separate audited action.
 */
final readonly class ServiceFamilyData
{
    /**
     * @param array<string, string|null> $name
     * @param array<string, string|null> $description
     */
    public function __construct(
        public string $code,
        public array $name,
        public array $description,
        public ServiceFamilyKind $kind,
        public ?string $icon,
        public int $sortOrder,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreServiceFamilyRequest|UpdateServiceFamilyRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $current = $request->route('serviceFamily');
        $icon = trim((string) ($data['icon'] ?? ''));

        return new self(
            code: strtolower(trim((string) ($data['code'] ?? ($current instanceof ServiceFamily ? $current->code : '')))),
            name: FormFieldDefinition::translations($data['name'] ?? []),
            description: FormFieldDefinition::translations($data['description'] ?? []),
            kind: ServiceFamilyKind::from((string) $data['kind']),
            icon: $icon === '' ? null : $icon,
            sortOrder: (int) ($data['sort_order'] ?? 0),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description === [] ? null : $this->description,
            'kind' => $this->kind,
            'icon' => $this->icon,
            'sort_order' => $this->sortOrder,
        ];
    }
}
