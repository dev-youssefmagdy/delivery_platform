<?php

namespace App\DTOs\Provider;

use App\DTOs\Catalog\FormFieldDefinition;
use App\Enums\Provider\DocumentSubject;
use App\Http\Requests\Admin\Provider\StoreDocumentTypeRequest;
use App\Http\Requests\Admin\Provider\UpdateDocumentTypeRequest;
use App\Models\DocumentType;

/**
 * Validated input of creating / updating a document type.
 */
final readonly class DocumentTypeData
{
    /**
     * @param array<string, string|null> $name
     * @param array<string, string|null> $description
     * @param list<string> $allowedMimes
     */
    public function __construct(
        public string $code,
        public array $name,
        public array $description,
        public DocumentSubject $appliesTo,
        public bool $requiresExpiry,
        public bool $requiresNumber,
        public array $allowedMimes,
        public int $maxSizeKb,
        public int $sortOrder,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreDocumentTypeRequest|UpdateDocumentTypeRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $current = $request->route('documentType');

        return new self(
            code: strtolower(trim((string) ($data['code'] ?? ($current instanceof DocumentType ? $current->code : '')))),
            name: FormFieldDefinition::translations($data['name'] ?? []),
            description: FormFieldDefinition::translations($data['description'] ?? []),
            appliesTo: DocumentSubject::from((string) ($data['applies_to'] ?? ($current instanceof DocumentType ? $current->applies_to->value : DocumentSubject::Provider->value))),
            requiresExpiry: (bool) ($data['requires_expiry'] ?? false),
            requiresNumber: (bool) ($data['requires_number'] ?? false),
            allowedMimes: array_values(array_map('strval', (array) ($data['allowed_mimes'] ?? []))),
            maxSizeKb: (int) ($data['max_size_kb'] ?? 5120),
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
            'applies_to' => $this->appliesTo,
            'requires_expiry' => $this->requiresExpiry,
            'requires_number' => $this->requiresNumber,
            'allowed_mimes' => $this->allowedMimes,
            'max_size_kb' => $this->maxSizeKb,
            'sort_order' => $this->sortOrder,
        ];
    }
}
